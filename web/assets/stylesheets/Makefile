BOOTSTRAP_DIR = ../../libs/twitter-bootstrap
FONTAWESOME_DIR = ../../libs/font-awesome/less
PUBLIC_DIR = ../public
CUSTOM_BOOTSTRAP_LESS = custom_bootstrap.less
AGENDAV_LESS = agendav.less
AGENDAV_PRINT_LESS = agendav.print.less
LESS_COMPRESSOR ?= `which lessc`
WATCHR ?= `which watchr`

#
# BUILD SIMPLE BOOTSTRAP DIRECTORY
# lessc & uglifyjs are required
#

css:
	cp ${BOOTSTRAP_DIR}/img/* ${PUBLIC_DIR}/img
	${LESS_COMPRESSOR} \
		--include-path=${BOOTSTRAP_DIR}/less:${FONTAWESOME_DIR} \
		${CUSTOM_BOOTSTRAP_LESS} > ${PUBLIC_DIR}/css/bootstrap.agendav.css 
	${LESS_COMPRESSOR} \
		--include-path=${BOOTSTRAP_DIR}/less:${FONTAWESOME_DIR} \
		${AGENDAV_LESS} > ${PUBLIC_DIR}/css/app.css 
	${LESS_COMPRESSOR} \
		--include-path=${BOOTSTRAP_DIR}/less:${FONTAWESOME_DIR} \
		${AGENDAV_PRINT_LESS} > ${PUBLIC_DIR}/css/app.print.css

#
# WATCH LESS FILES
#

watch:
	echo "Watching less files..."; \
	watchr -e "watch('*\.less') { system 'make' }"


.PHONY: docs watch gh-pages
