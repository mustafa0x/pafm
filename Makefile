MINIFIER = ~/dev/yuicompressor-2.4.7.jar
VERSION ?= `cat pafm.php | grep VERSION | grep -o '[0-9].*[0-9]'`
BUILD = build
TMP = ${BUILD}/tmp

FILES = pafm-files

PAFM = pafm.php
PAFM_BUILD = ${BUILD}/${PAFM}

JS_PATH = ${FILES}/js.js
CSS_PATH = ${FILES}/style.css

IMAGES_PATH = ${FILES}/images
IMAGES_LIST = $(patsubst ${IMAGES_PATH}/%, %, $(wildcard ${IMAGES_PATH}/*.png ${IMAGES_PATH}/*.gif))
#	${IMAGES_PATH} is removed so dependencies get built

RESOURCE_LN = `cat ${PAFM} | grep -n _R\ = | cut -d : -f 1`

${PAFM_BUILD}: js css ${IMAGES_LIST}
	head -n${RESOURCE_LN} ${PAFM} | sed 's/DEV\(.*\)1/DEV\10/' > $@
	printf "%s\n" "`cat ${TMP}`" >> $@
	tail -n +$(shell echo $$(($(RESOURCE_LN)+1))) ${PAFM}  >> $@
	rm ${TMP}
	@printf "\nBuild complete, check readme & version numbers \n"

js:
	printf "\$$_R['js'] = '%s';" "`java -jar ${MINIFIER} --type js -v ${JS_PATH}`" | sed "s/pafm-files\/images\//?r=images\//g" >> ${TMP}

css:
	printf "\n\$$_R['css'] = '%s';" "`java -jar ${MINIFIER} --type css -v ${CSS_PATH}`" | sed "s/images\//?r=images\//g" >> ${TMP}

${IMAGES_LIST}: ;
	printf "\n\$$_R['images/$@'] = '%s';" "`cat ${IMAGES_PATH}/$@ | base64 -w0`" >> ${TMP}

zip: ${PAFM_BUILD}
	rm -f ${BUILD}/pafm-${VERSION}.zip
	zip -j ${BUILD}/pafm-${VERSION}.zip ${PAFM_BUILD}
	@printf "\nZipped\n"
#TODO:
#	check for and escape single quotes (YUI replaces ' with ")
#	better version check
#	css sprites
#	js & php lint
