#!/bin/sh
# Пересборка ассетов плагина из соседнего репозитория ARMS.
# Использование: ./sync_assets.sh [путь_к_arms]   (по умолчанию ../arms)
#
# style.css ГЕНЕРИРУЕТСЯ: файлы ARMS + style.local.css (локальные правила
# и переопределения — редактировать надо его, не style.css).
# JS копируется как есть: tooltipster.bundle.js в ARMS содержит LOCAL PATCH
# (realSize/anti-sausage/skipReposition) — без него qtip_ajax.js не работает.
set -e
ARMS="${1:-../arms}"

[ -d "$ARMS/web" ] || { echo "ARMS не найден в '$ARMS'" >&2; exit 1; }

{
	echo "/* style.css СГЕНЕРИРОВАН sync_assets.sh — руками не редактировать!"
	echo "   Локальные правки — в style.local.css, остальное — в репозитории ARMS. */"
	for f in \
		web/tooltipster/css/tooltipster.bundle.min.css \
		web/tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-shadow.min.css \
		web/tooltipster/css/tooltip-yellow.css \
		web/css/qtip.css \
		web/css/state-colors.css \
		web/css/markers.css \
		web/css/tables.css \
		web/css/arm-map.css \
		web/css/arm-passport.css \
		web/css/scans.css \
		web/css/codes.private.css \
		components/assets/ExpandableCardWidgetAsset/css/card.css \
	; do
		echo
		echo "/* ==================== arms:$f ==================== */"
		cat "$ARMS/$f"
	done
	echo
	echo "/* ==================== local: style.local.css ==================== */"
	cat style.local.css
} > style.css

cp "$ARMS/web/tooltipster/js/tooltipster.main.js"   tooltipster.main.js
cp "$ARMS/web/tooltipster/js/tooltipster.bundle.js" tooltipster.bundle.js
cp "$ARMS/web/tooltipster/js/qtip_ajax.js"          qtip_ajax.js
cp "$ARMS/components/assets/ExpandableCardWidgetAsset/js/switch.js" switch.js

echo "OK: style.css собран, js скопирован из $ARMS"
