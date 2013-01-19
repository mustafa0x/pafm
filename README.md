# PHP AJAX File Manager

## About
PHP AJAX File Manager (PAFM) is a web file manager based on the [KISS Principle](http://en.wikipedia.org/wiki/KISS_principle "Keep it simple, stupid").

It is intended for use by web masters who need a simple way to interact with their files. As much control as possible is given, which makes it only suitable for those who already have complete access.

## Installation

Get [build/pafm.php](https://raw.github.com/mustafa0x/pafm/master/build/pafm.php) or see the downloads at [Sourceforge](http://sourceforge.net/projects/pafm/files/).

Open pafm.php with your code editor of choice (note that you can change the password of pafm *using* pafm)
then scroll down till you see `/** configuration **/`

* `PASSWORD` is, obviously, the password for PAFM. The default password is `auth`.

* `ROOT` is the path to the directory you want to
  manage (this is done by `chdir`).
  *  E.g. if you want to manage your home directory,
  change `ROOT` to `/home`

## Screenshots

### Login:

![pafm login][1]
[1]: http://mus.tafa.us/projects/pafm/images/login-sm.png "login window"

### File List:

![pafm list][2]
[2]: http://mus.tafa.us/projects/pafm/images/list-sm.png "file listing and functions"

### Upload:

![pafm upload][3]
[3]: http://mus.tafa.us/projects/pafm/images/upload-sm.png "the upload window"

## Recent Changes

### 1.7
  * Added experimental terminal/shell
  * Added drag-and-drop upload
  * Added support for copying entire directories
  * Added file & folder count
  * The complete path is now shown in the breadcrumbs
  * Timezone offset is now used when displaying timestamps
  * Changed CSRF protection; the nonce is now generated per session
  * Paths are no longer sanitized, as pafm doesn't
    attempt to prevent the user from intentional behavior
  * When leaving file editing, the user is prompted if unsaved changes were made.
  * The password is now encrypted before being stored in the session
  * Minor fixes and style changes

### 1.6
  * Added CSRF protection
  * Fixed bug in bruteforce protection
  * CodeMirror installation more secure
  * Minor code and CSS changes

### 1.5.7
  * Added bruteforce protection
  * Improved file list sorting
  * Minor code and CSS changes

### 1.5.6
  * Minor changes

### 1.5.5
  * Rewrote CodeMirror installation method
  * Minor code changes

### 1.5.3
  * Bug fixes

### 1.5
  * CodeMirror Added
  * Minor bug fixes

### 1.4.1
  * Fixed upload-check bug in Chrome
  * Fixed bug with move list directory icon
  * Added warning when the password is default

### 1.4
  *   One-file release

    > This is made possible by [Makefile](https://github.com/mustafa0x/pafm/blob/master/Makefile), which combines the project into a single file, for portability.

### 1.3
  * HTML5 uploading

### 1.2
  * File Copying
  * Remote Copy
  * File Last Modified Column

### 1.0.6
  * Removed CodePress
  * ROOT directive changes
  * Display version number
  * Multiple file upload


## Future
  * Rewrite folder/file loops
  * Remove onclick events
  * Hash file operations (e.g. `#edit&file=foo.bar`)
  * AJAX-ify existing refreshes
  * File and folder sorting
