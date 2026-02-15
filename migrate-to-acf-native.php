<?php

namespace Flynt\Tools;

// Admin page to migrate hidden layouts from flyntwp/acf-hide-layout to ACF native deactivate

// Register submenu under Tools
\add_action('admin_menu', function () {
    \add_management_page(
        'Migrate ACF Hidden Layouts',
        'Migrate ACF Hidden Layouts',
        'manage_options',
        'flynt-migrate-acf-hide-layout',
        __NAMESPACE__ . '\\renderMigrationPage'
    );
});

/**
 * Render the migration admin page
 */
function renderMigrationPage()
{
    if (!\current_user_can('manage_options')) {
        \wp_die(\__('You do not have sufficient permissions to access this page.'));
    }

    $isSubmitted = isset($_POST['flynt_migrate_run']);
    $dryRun = isset($_POST['flynt_migrate_dry_run']);

    echo '<div class="wrap">';
    echo '<h1>Migrate ACF Hidden Layouts</h1>';
    echo '<p>This tool migrates hidden layouts created by the plugin <code>flyntwp/acf-hide-layout</code> to ACF\'s native "Deactivate layout" feature.</p>';

    echo '<form method="post">';
    \wp_nonce_field('flynt_migrate_acf_hide_layout', 'flynt_migrate_acf_hide_layout_nonce');
    echo '<p><label><input type="checkbox" name="flynt_migrate_dry_run" value="1" checked> Dry run (no database changes)</label></p>';
    echo '<p><button type="submit" name="flynt_migrate_run" class="button button-primary">Run Migration</button></p>';
    echo '</form>';

    if ($isSubmitted && \check_admin_referer('flynt_migrate_acf_hide_layout', 'flynt_migrate_acf_hide_layout_nonce')) {
        $results = migrateHiddenLayouts((bool) $dryRun);
        renderResults($results, (bool) $dryRun);
    }

    echo '</div>';
}

/**
 * Perform migration of hidden layouts to ACF native layout_meta disabled list
 *
 * @param bool $dryRun If true, do not persist changes
 * @return array Summary of actions and per-post logs
 */
function migrateHiddenLayouts($dryRun = true)
{
    $summary = [
        'dryRun' => $dryRun,
        'checkedPosts' => 0,
        'updatedPosts' => 0,
        'errors' => [],
        'logs' => [],
    ];

    // Check function availability
    $hasAcf = \function_exists('acf_get_fields') || \function_exists('get_field_objects');
    $hasAcfMetaApi = \function_exists('acf_update_metadata_by_field');

    if (!$hasAcf || !$hasAcfMetaApi) {
        $summary['errors'][] = 'Required ACF functions are not available. Ensure ACF Pro 6.0+ is active.';
        return $summary;
    }

    // Retrieve all public post types including custom, exclude revisions and attachments
    $postTypes = \get_post_types(['public' => true], 'names');

    $queryArgs = [
        'post_type' => array_values($postTypes),
        'post_status' => ['publish', 'draft', 'pending', 'future', 'private'],
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    ];

    $postIds = \get_posts($queryArgs);

    foreach ($postIds as $postId) {
        $summary['checkedPosts']++;

        $postLog = [
            'postId' => $postId,
            'fieldsProcessed' => 0,
            'layoutsFound' => 0,
            'layoutsHiddenDetected' => 0,
            'layoutsNewlyDisabled' => 0,
            'fieldLogs' => [],
        ];

        // Get all ACF field objects attached to this post
        $fieldObjects = \function_exists('get_field_objects') ? \call_user_func('get_field_objects', $postId) : null;
        if (empty($fieldObjects)) {
            $summary['logs'][] = $postLog;
            continue;
        }

        foreach ($fieldObjects as $fieldName => $fieldObject) {
            if (!isset($fieldObject['type']) || $fieldObject['type'] !== 'flexible_content') {
                continue;
            }

            $postLog['fieldsProcessed']++;

            // We need to iterate rows of this flexible content
            $hasRows = \function_exists('have_rows') ? (bool) \call_user_func('have_rows', $fieldName, $postId) : false;
            if (!$hasRows) {
                continue;
            }

            // Collect disabled indices for this field
            $disabledLayouts = [];
            $renamedLayouts = [];

            // If there is existing layout_meta, preserve it
            $existingLayoutMeta = \get_post_meta($postId, '_' . $fieldName . '_layout_meta', true);
            if (is_array($existingLayoutMeta)) {
                if (!empty($existingLayoutMeta['disabled']) && is_array($existingLayoutMeta['disabled'])) {
                    $disabledLayouts = array_values(array_unique(array_map('intval', $existingLayoutMeta['disabled'])));
                }
                if (!empty($existingLayoutMeta['renamed']) && is_array($existingLayoutMeta['renamed'])) {
                    $renamedLayouts = $existingLayoutMeta['renamed'];
                }
            }

            // Iterate rows and detect plugin hidden flags
            if (\call_user_func('have_rows', $fieldName, $postId)) {
                $rowIndex = 0;
                while (\call_user_func('have_rows', $fieldName, $postId)) {
                    \call_user_func('the_row');
                    $postLog['layoutsFound']++;

                    // Determine the plugin field key suffix used for hidden flag
                    // Per plugin: name is "{$field['name']}_{$key}_{$field_key}" where $field_key is acf_hide_layout
                    // Here, $key is zero-based row index in stored meta for flexible content
                    $hideFieldName = $fieldName . '_' . $rowIndex . '_acf_hide_layout';

                    // Build fake field array to use acf_get_value API similar to plugin's read method
                    $hideLayoutField = [
                        'name' => $hideFieldName,
                        'key' => 'field_acf_hide_layout',
                    ];

                    $isHidden = null;
                    if (\function_exists('acf_get_value')) {
                        $isHidden = \call_user_func('acf_get_value', $postId, $hideLayoutField);
                    } else {
                        $isHidden = \get_post_meta($postId, $hideFieldName, true);
                    }

                    if (!empty($isHidden)) {
                        $postLog['layoutsHiddenDetected']++;
                        if (!in_array($rowIndex, $disabledLayouts, true)) {
                            $disabledLayouts[] = $rowIndex;
                            $postLog['layoutsNewlyDisabled']++;
                        }
                    }

                    $rowIndex++;
                }
            }

            sort($disabledLayouts);

            // If changes, write layout_meta using ACF API
            $fieldLog = [
                'fieldName' => $fieldName,
                'disabled' => $disabledLayouts,
                'renamed' => $renamedLayouts,
            ];

            if (!empty($disabledLayouts)) {
                if (!$dryRun) {
                    if (\function_exists('acf_update_metadata_by_field')) {
                        \call_user_func(
                            'acf_update_metadata_by_field',
                            $postId,
                            [
                                'name' => '_' . $fieldName . '_layout_meta',
                            ],
                            [
                                'disabled' => $disabledLayouts,
                                'renamed' => $renamedLayouts,
                            ]
                        );
                        $summary['updatedPosts']++;
                    } else {
                        // Fallback to update_post_meta if ACF helper is not available
                        \update_post_meta($postId, '_' . $fieldName . '_layout_meta', [
                            'disabled' => $disabledLayouts,
                            'renamed' => $renamedLayouts,
                        ]);
                        $summary['updatedPosts']++;
                    }
                }
            }

            $postLog['fieldLogs'][] = $fieldLog;
        }

        $summary['logs'][] = $postLog;
    }

    return $summary;
}

/**
 * Render migration results in admin UI
 *
 * @param array $results
 * @param bool $dryRun
 * @return void
 */
function renderResults($results, $dryRun)
{
    if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
            echo '<div class="notice notice-error"><p>' . \esc_html($error) . '</p></div>';
        }
        return;
    }

    $summaryText = sprintf(
        'Processed %d posts. %s %d post(s) were updated.',
        (int) $results['checkedPosts'],
        $dryRun ? 'Dry run: no changes saved.' : 'Changes saved.',
        (int) $results['updatedPosts']
    );

    echo '<div class="notice notice-success"><p>' . \esc_html($summaryText) . '</p></div>';

    echo '<h2>Details</h2>';
    echo '<div style="max-height: 500px; overflow: auto; background: #fff; padding: 12px; border: 1px solid #ccd0d4;">';

    foreach ($results['logs'] as $postLog) {
        echo '<div style="margin-bottom: 16px;">';
        echo '<h3 style="margin: 0 0 8px;">Post ID ' . intval($postLog['postId']) . '</h3>';
        echo '<p style="margin: 0 0 8px;">Fields processed: ' . intval($postLog['fieldsProcessed']) . ' | Layouts found: ' . intval($postLog['layoutsFound']) . ' | Hidden detected: ' . intval($postLog['layoutsHiddenDetected']) . ' | Newly disabled: ' . intval($postLog['layoutsNewlyDisabled']) . '</p>'; 

        if (!empty($postLog['fieldLogs'])) {
            echo '<ul style="margin: 0 0 8px 18px; list-style: disc;">';
            foreach ($postLog['fieldLogs'] as $fieldLog) {
                $disabled = !empty($fieldLog['disabled']) ? implode(', ', array_map('intval', $fieldLog['disabled'])) : 'none';
                echo '<li><strong>' . \esc_html($fieldLog['fieldName']) . ':</strong> disabled [' . \esc_html($disabled) . ']</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
    }

    echo '</div>';
}

