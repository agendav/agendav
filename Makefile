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

.PHONY: all clean composer composer-dev templates less

