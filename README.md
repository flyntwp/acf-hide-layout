# ACF Hide Layout

https://flyntwp.com/acf-hide-layout/

Easily hide the layout of the flexible content on the frontend but still keep it in the backend.

Sometimes you may need to hide/remove a flexible content layout from showing on the frontend of the website,
but you would still like to keep it in the backend in case you need to re-enable that layout again in the future.
Of course you can always just remove the layout, but if it’s a complex group of fields with a lot of data,
re-creating it later would be a pain. And here the **ACF Hide Layout** plugin comes into play. It adds a small button with an "eye" icon to easily disable/enable flexible layout content without removing it.

## How to use it?

Next to the flexible content options "Add Layout" and "Remove Layout" is a new option "Hide / Show Layout".
Toggling that option will hide or show the layout on the fronted.

## Dependencies
* [WordPress](https://wordpress.org/) >= 4.7
* [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/) >= 5.7

## Install
1. Clone this repo to `/wp-content/plugins`.
2. Activate the plugin through the 'Plugins' screen in WordPress.

## Publish the plugin to wordpress.org

To publish the plugin to the official [wordpress.org/plugins/acf-hide-layout/](https://wordpress.org/plugins/acf-hide-layout/) page we will need to use SVN. Read more about about [using subversion with WordPress](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)

SVN and the Plugin Directory are a release repository. Unlike Git, you shouldn’t commit every small change, as doing so can degrade performance. Please only push finished changes to your SVN repository.

SVN uses the wordpress.org account username and password. The username is bleechberlin. For the initital commit SVN might ask for password for a different username (usually your OS username) but just enter empty password and then you can enter new username and password.

0. Dont't forget to update the plugin version in the `acf-hide-layout.php` and in the `readme.txt`. Don't update plugin version if you are only updating readme.txt and images in `svn/assets`.
1. Make sure you have SVN installed.
    * To install it with homebrew run `brew install subversion`.
2. Run `./run svn_setup`.
    * This will create a new folder `svn` and connect it with plugin SVN repository.
3. When you have made changes to the plugin use `./run svn_update_trunk`.
    * This will copy all the plugin folders and files to `svn/trunk` folder (except those in the `svn-exclude-list.txt`).
4. Go into `svn` folder with `cd svn` and use `svn` commands to add, commit/push the changes. Here are few useful commands
    * `svn up` Update SVN repository, like `git pull`
    * `svn status` See status of files, e.g. which are added (A), modified(M) or not added(?)
    * `svn add trunk/*` Adds all files in the trunk folder
    * `svn ci -m 'feat: add something new'` Commits and pushes the changes
    * `svn cp trunk tags/1.1` Create tag 1.1. Commit it `svn ci -m "tagging version 1.1"`

Read more about ["Tagging" New Versions](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/#tagging-new-versions)

## Maintainers
This project is maintained by [bleech](https://github.com/bleech).

## License
GPLv2
