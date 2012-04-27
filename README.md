# PHP AJAX File Manager

## About
PHP AJAX File Manager (PAFM) is a web file manager influenced by the [KISS Principle](http://en.wikipedia.org/wiki/KISS_principle "Keep it simple, stupid").

## Installation

Open pafm.php with your code editor of choice (note that you can change the password of pafm *using* pafm)
then scroll down till you see `/** configuration **/`

* `PASSWORD` is, obviously, the password for PAFM. The default password is `auth`.

* `ROOT` is the path to the directory you want to
  manage (this is done by `chdir`).
  *  E.g. if you want to manage your home directoy,
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

    > This is made possible by [Makefile](https://github.com/mustafa0x/pafm/blob/master/Makefile), which combines the project into a single file, for portablility.

### 1.3
  * HTML5 uploading

### 1.2
  * File Copying
  * Remote Copy
  * File Last Modifed Column

### 1.0.6
  * Removed CodePress
  * ROOT directive changes
  * Display version number
  * Multiple file upload


## Future
  * Rewrite folder/file loops
  * Remove onclick events
  * PHP Shell
  * Hash file operations (e.g. `#edit&file=foo.bar`)
