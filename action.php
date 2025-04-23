<?php
/**
 * DokuWiki Plugin inventory (Action Component)
 * Обработчик AJAX запросов
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Alexander Reviakin <reviakin@gmail.com>
 */

use dokuwiki\Remote\Api;

if (!defined('DOKU_INC')) die();

//include __DIR__.'/inventory.php'; //included in syntax.php
include __DIR__.'/syntax.php';

class action_plugin_inventory extends DokuWiki_Action_Plugin
{

    const PLUGIN_NAME = 'inventory';

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'hook');
    }

    /**
     * handle ajax requests
     * @param $event Doku_Event
     */
    public function hook(&$event)
    {

        if ($event->data !== self::getPluginName()) {
            return;
        }

        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        //global $conf;
        //$response_code = 200;

        global $INPUT;
        $action = $INPUT->str('action');

        $info='temporary';

        switch ($action) {
            //Асинхронный парсинг синтаксических вставок
            //чтобы при рендере страницы быстрее отдавать результат из кэша, а обновлять данные в фоне
            case 'parse':
                $data = $INPUT->str('data');
                if ($data == '') {
                    http_response_code(400);
                    echo 'No data requested';
                    return;
                }
				$parsed=syntax_plugin_inventory::parseData($data);
				if (is_string($parsed)) {
                    http_response_code(200);
                    echo $parsed;
					return;
				}
				$inventory=new inventoryInterface(
					$this->getConf('inventory_url'),
					$this->getConf('inventory_user'),
					$this->getConf('inventory_password'),
					$this->getConf('inventory_cache')
				);

				echo $inventory->fetchInventory($parsed[0],$parsed[1],false);
				return;

            //подгрузка данных инвентори для тултипа
            case 'ttip':
                $data = urldecode($INPUT->str('data'));
                if ($data == '') {
                    http_response_code(400);
                    echo 'No data requested';
                    return;
                }
                $inventory=new inventoryInterface(
                    $this->getConf('inventory_url'),
                    $this->getConf('inventory_user'),
                    $this->getConf('inventory_password'),
                    $this->getConf('inventory_cache')
                );

                echo $inventory->fetchTtip($data);
                return;

            //загрузка секции страницы
            case 'page':
                $page = $INPUT->str('page');

                if (!page_exists($page)) {
                    http_response_code(404);
                    echo "Page not found";
                    return;
                }
                $section = $INPUT->str('section');
                $instructions = p_get_instructions("{{page>$page#$section}}");
                $html = p_render('xhtml', $instructions, $info);

                header('Content-Type: text/html');
                echo $html;
                return;

            //загрузка секции страницы
            case 'render':
                $code = file_get_contents('php://input');

                if (empty($wikitext)) {
                    http_response_code(400);
                    echo "Error: No code provided";
                    return;
                }

                $instructions = p_get_instructions($code);
                $html = p_render('xhtml', $instructions, $info);

                header('Content-Type: text/html');
                echo $html;
                return;

            default:
                http_response_code(404);
                echo 'Unknown action: '.$action;
        }
    }
}