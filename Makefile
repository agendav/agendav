BIN=./node_modules/.bin
WEB_DIR=web
ASSETS=assets
OUTPUT_DIR=${WEB_DIR}/public
TEMPLATES_DIR=${WEB_DIR}/assets/templates
TEMPLATES_OUTPUT=${OUTPUT_DIR}/js/templates/templates.js

clean:
	rm -rf node_modules

composer:
	cd web; composer install --no-dev --prefer-dist

composer-dev:
	cd web; composer install --dev --prefer-source

templates:
	${BIN}/dustc \
		--pwd ${TEMPLATES_DIR} \
		-o ${TEMPLATES_OUTPUT} \
		${TEMPLATES_DIR}/*.dust

less:
	${BIN}/lessc \
		${ASSETS}/stylesheets/agendav.less \
		${OUTPUT_DIR}/css/agendav.css

css:
	# TODO read version from package.json?
	cat ${WEB_DIR}/public/css/agendav.css \
		${WEB_DIR}/public/css/jquery-ui.css \
		${WEB_DIR}/public/css/jquery-ui.structure.css \
		${WEB_DIR}/public/css/jquery-ui.theme.css \
		${WEB_DIR}/public/css/fullcalendar.css \
		${WEB_DIR}/public/css/jquery.qtip.css \
		${WEB_DIR}/public/css/freeow.css \
		${WEB_DIR}/public/css/jquery.timepicker.css \
		${WEB_DIR}/public/css/colorpicker.css > \
		${OUTPUT_DIR}/css/agendav-built-2.0.1.css

	cat ${WEB_DIR}/public/css/app.print.css \
		${WEB_DIR}/public/css/fullcalendar.print.css > \
		${OUTPUT_DIR}/css/agendav-built-print-2.0.1.css

js:
	# TODO version
	cat \
		${WEB_DIR}/public/js/libs/jquery.js \
		${WEB_DIR}/public/js/libs/moment.js \
		${WEB_DIR}/public/js/libs/moment-timezone-with-data-2010-2020.min.js > \
		${OUTPUT_DIR}/js/agendav-built-2.0.1.js
	echo >> ${OUTPUT_DIR}/js/agendav-built-2.0.1.js
	cat \
		${WEB_DIR}/public/js/libs/button.js \
		${WEB_DIR}/public/js/libs/jquery-ui.js \
		${WEB_DIR}/public/js/libs/tab.js \
		${WEB_DIR}/public/js/libs/jquery.timepicker.js \
		${WEB_DIR}/public/js/libs/jquery.freeow.min.js >> \
		${OUTPUT_DIR}/js/agendav-built-2.0.1.js
	echo >> ${OUTPUT_DIR}/js/agendav-built-2.0.1.js
	cat \
		${WEB_DIR}/public/js/libs/jquery.colorPicker.js \
		${WEB_DIR}/public/js/libs/imagesloaded.pkg.min.js >> \
		${OUTPUT_DIR}/js/agendav-built-2.0.1.js
	echo >> ${OUTPUT_DIR}/js/agendav-built-2.0.1.js
	cat \
		${WEB_DIR}/public/js/libs/jquery.qtip.js \
		${WEB_DIR}/public/js/libs/jquery.serializeobject.js \
		${WEB_DIR}/public/js/libs/fullcalendar.js \
		${WEB_DIR}/public/js/libs/rrule.js \
		${WEB_DIR}/public/js/libs/nlp.js \
		${WEB_DIR}/public/js/templates/dust-core.js \
		${WEB_DIR}/public/js/templates/dust-helpers.js \
		${WEB_DIR}/public/js/templates/templates.js \
		${WEB_DIR}/public/js/datetime.js \
		${WEB_DIR}/public/js/repeat-form.js \
		${WEB_DIR}/public/js/app.js >> \
		${OUTPUT_DIR}/js/agendav-built-2.0.1.js


.PHONY: all clean composer composer-dev templates less css

