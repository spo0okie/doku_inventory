<?php
/**
 * DokuWiki Plugin inventory (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Alexander Reviakin <reviakin@gmail.com>
 */

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
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $data = mb_substr($match,12,-2);
        return $data;
    }

	/**
	 * Разбирается с каким кодом вернулась страничка с запроса
	 */
    private static function parseHeaders( $headers )
	{
		//упер отсель https://www.php.net/manual/ru/reserved.variables.httpresponseheader.php
		$head = array();
		foreach( $headers as $k=>$v )
		{
			$t = explode( ':', $v, 2 );
			if( isset( $t[1] ) )
				$head[ trim($t[0]) ] = trim( $t[1] );
			else
			{
				$head[] = $v;
				if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
					$head['response_code'] = intval($out[1]);
			}
		}
		return $head;
	}

	private function fetchInventoryPage ($url,$name_replacement=null)
	{
		$api=$this->getConf('inventory_url');
		$page=@file_get_contents($url);
		$response=static::parseHeaders($http_response_header);
		if (isset($response['response_code'])&&($response['response_code']=='200')) {
			$page=str_replace('href="/web','href="'.$api,$page);
			$page=str_replace('qtip_ajxhrf="/web','qtip_ajxhrf="'.$api,$page);
			if (!empty($name_replacement)) {
                $page=preg_replace('/<a(.+)>.+<\/a>/',"<a {1}>$name_replacement</a>",$page,1);
            }
			return $page;
		}

		return 'ОШИБКА: объект не найден в инвентаризации: '.$response['response_code']; //.$url;
	}

    /**
     * Сюда передаем распарсенные данные из синтаксиса
     * отвечаем уже отрендеренными HTML данными
     * @param $data
     * @return false|string|string[]
     */
    private function fetchInventory($data,$name_replacement=null) {
    	//return 'inventory';
		$controller=$data[0];
		$id=$data[1];
		$method=isset($data[2])?($data[2]):'item';
		$api=$this->getConf('inventory_url');

		switch ($controller) {

			case 'service':
                /**
                 * Запрошен сервис
                 */
				switch ($method) {
                    case 'support':
					    //{{inventory:service:11:support}}
						return $this->fetchInventoryPage($api.'/services/card-support?id='.$id);
						break;
					case 'item':
						if (empty($id)) return '<a href="'.$api.'/services/">Укажите номер сервиса в инвентаризации</a>' ;
						return $this->fetchInventoryPage($api.'/services/'.$method.'?id='.$id,$name_replacement);
						break;
					default: return 'ОШИБКА: Неизвестный элемент сервиса';
				}
				break;

			case 'user':
				if (is_numeric($id)) {
					return $this->fetchInventoryPage($api.'/users/item?id='.$id,$name_replacement);
				} elseif (strpos($id,'')===false) {
					return $this->fetchInventoryPage($api.'/users/item-by-login?login='.$id,$name_replacement);
				} else {
					return $this->fetchInventoryPage($api.'/users/item-by-name?name='.$id,$name_replacement);
				}
				break;

			case 'comp':
			case 'os':
				if (is_numeric($id)) {
					return $this->fetchInventoryPage($api.'/comps/item?id='.$id,$name_replacement);
				} else {
					return $this->fetchInventoryPage($api.'/comps/item-by-name?name='.$id,$name_replacement);
				}
				break;

			case 'tech_model':
				if (is_numeric($id)) {
					return $this->fetchInventoryPage($api.'/tech-models/item?id='.$id.'&long=1',$name_replacement);
				} else {
					$tokens=explode('/',$id);
					if (count($tokens)!=2) return 'ОШИБКА: не удалось определить производителя/модель';

					return $this->fetchInventoryPage($api.'/tech-models/item-by-name?name='.urlencode($tokens[1]).'&manufacturer='.urlencode($tokens[0]).'&long=1',$name_replacement);
				}
				break;

			case 'tech':
				if (is_numeric($id)) {
					return $this->fetchInventoryPage($api.'/techs/item?id='.$id,$name_replacement);
				} else {
					$tokens=explode('/',$id);
					if (count($tokens)!=2) return 'ОШИБКА: не удалось определить инв.номер оборудования';

					return $this->fetchInventoryPage($api.'/techs/item-by-name?name='.urlencode($tokens[0]),$name_replacement);
				}
				break;

            case 'org-phones':
                if (is_numeric($id)) {
                    return $this->fetchInventoryPage($api.'/'.$controller.'/item?id='.$id,$name_replacement);
                } return 'Поддерживается ссылка только через ID';
                break;

            default:
				return 'ОШИБКА: неизвестный тип объекта';
		}
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
		//$renderer->doc .= '<div>';

		if (!mb_strlen($data)) {
			$renderer->doc .= 'ОШИБКА: Пустая ссылка на инвентаризацию';
		} else {
		    //если есть кусок после | то имя выводимого объекта надо заменить на это
		    if (strpos($data,'|')) {
		        $name_replacement=substr($data,strpos($data,'|')+1);
		        $data=substr($data,0,strpos($data,'|'));
            } else {
		        $name_replacement=null;
            }
			$tokens=explode(':',$data);
			if (count($tokens)==2 || count($tokens)==3) {
				//$renderer->doc .= 'kорректная ссылка на инвентаризацию';
				$renderer->doc .= $this->fetchInventory($tokens,$name_replacement);
			} else {
				$renderer->doc .= 'ОШИБКА: Некорректная ссылка на инвентаризацию';
			}
		}

		//$renderer->doc .= '</div>';
        return true;
    }
}

