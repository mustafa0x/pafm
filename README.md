# PHP AJAX File Manager

## Installation

Open pafm.php with your code editor of choice (note that you can change the password of pafm *using* pafm)
then scroll down till you see `/** configuration **/`

* `PASSWORD` is, obviously, the password for PAFM.

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

## Upcoming

* CodeMirror

## Wishlist

* Updating functionality
* PHP Shell
* Hash file operations (e.g. `#edit&file=foo.bar`)
