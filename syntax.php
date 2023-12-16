<?php
/**
 * DokuWiki Plugin inventory (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Alexander Reviakin <reviakin@gmail.com>
 */

include 'inventory.php';

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

class syntax_plugin_inventory extends DokuWiki_Syntax_Plugin
{


    /**
     * @return string Syntax mode type
     */
    public function getType()
    {
        return 'substition';
    }

    /**
     * @return string Paragraph type
     */
    public function getPType()
    {
        return 'normal';
    }

    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort()
    {
        return 64;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('{{inventory:.*?}}', $mode, 'plugin_inventory');
    }

    /**
     * Handle matches of the inventory syntax
     *
     * @param string       $match   The match of the syntax
     * @param int          $state   The state of the handler
     * @param int          $pos     The position in the document
     * @param Doku_Handler $handler The handler
     *
     * @return string Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        return mb_substr($match,12,-2);
    }


    /**
     * Render xhtml output or metadata
     *
     * @param string        $mode     Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer $renderer The renderer
     * @param array         $data     The data from the handler() function
     *
     * @return bool If rendering was successful.
     */
    public static function parseData($data)
    {
		if (!mb_strlen($data)) return 'ОШИБКА: Пустая ссылка на инвентаризацию';

		//если есть кусок после | то имя выводимого объекта надо заменить на это
		if (strpos($data,'|')) {
			$name_replacement=substr($data,strpos($data,'|')+1);
			$data=substr($data,0,strpos($data,'|'));
		} else {
			$name_replacement=null;
		}

		$tokens=explode(':',$data);
		if (count($tokens)==2 || count($tokens)==3) {
			return [$tokens,$name_replacement];
		}

		return 'ОШИБКА: Некорректная ссылка на инвентаризацию';
	}


    /**
     * Render xhtml output or metadata
     *
     * @param string        $mode     Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer $renderer The renderer
     * @param array         $data     The data from the handler() function
     *
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
		if ($mode !== 'xhtml') {
			return false;
		}

		$parsed=static::parseData($data);

		if (is_string($parsed)) {
			$rendered = $parsed;
		} else {

			$tokens=$parsed[0];
			$name=$parsed[1];

			$inventory=new inventoryInterface(
				$this->getConf('inventory_url'),
				$this->getConf('inventory_user'),
				$this->getConf('inventory_password'),
				$this->getConf('inventory_cache')
			);
			$rendered=$inventory->fetchInventory($tokens,$name);
		}
		$rendered=trim($rendered);
		//если данные завернуты в span, значит они inline и их самих тоже можно завернуть в span
	    //иначе заворачиваем в div
		$renderer->doc .= substr($rendered,0,5)==='<span'?
			'<span class="inventory_plugin_item muted" data-update="'.urlencode($data).'">'.$rendered.'</span>':
			'<div class="inventory_plugin_item muted" data-update="'.urlencode($data).'">'.$rendered.'</div>';

		return true;
    }
}

