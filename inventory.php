<?php
/**
 * DokuWiki Plugin inventory (Inventory API Lib)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Alexander Reviakin <reviakin@gmail.com>
 */


class inventoryInterface
{

	public $api;
	public $user;
	public $pass;
	public $cache;
	private $response;
	private $page;

	function __construct($api,$user,$pass,$cache)
	{
		$this->api=$api;
		$this->user=$user;
		$this->pass=$pass;
		$this->cache=$cache;
	}

	private function fetchPage ($url,$load_ttip=true)
	{
		$api=$this->api;
		$user=$this->user;
		$pass=$this->pass;
		$cache=$this->cache;
		$ch=curl_init($url);
		curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$page=@curl_exec($ch);
		// Check if any error occurred
		if (!curl_errno($ch)) {
			$this->response=curl_getinfo($ch);
		}
		//echo $page;
		$this->page=$page;
		if (isset($this->response['http_code'])&&($this->response['http_code']==200)) {
			$page=str_replace('href="/web','href="'.$api,$page);
			$page=str_replace('src="/web','src="'.$api,$page);
			if ($load_ttip) {
				$matches=[];
				while (preg_match('/qtip_ajxhrf="\/web([^"]+)"/',$page,$matches)) {
					$ttipUrl=$matches[1];
					$ttipReplace='qtip_ajxhrf="/lib/exe/ajax.php?call=inventory&action=ttip&data='.urlencode($ttipUrl).'"';
					//$ttipContent=$this->fetchPage($api.$ttipUrl,false);
					$page=str_replace($matches[0],$ttipReplace,$page);
				}
			}
			$page=str_replace('qtip_ajxhrf="/web','qtip_ajxhrf="'.$api,$page);
			return $page;
		}
		return null;
	}

	public function fetchTtip($ttipUrl) {
	    return $this->fetchPage($this->api.$ttipUrl);
    }


	public function parsePage ($page,$name_replacement=null,$not_found_text=null)
	{
		if (is_null($page)) return is_null($not_found_text)?
			'ОШИБКА: объект не найден в инвентаризации: '.$this->response['http_code']:
			$not_found_text; //.$this->response['response_code'].$this->page;
		if (!empty($name_replacement)) {
			$page=preg_replace('/<span class=[\'"]item-name[\'"]>(?:(?!<\/span>).)*<\/span>/',$name_replacement,$page,1);
		}
		return $page;
	}

	public function cacheFile($data)
	{
		//error_log($this->cache.'/'.str_replace('/','_',$data));
		return $this->cache.'/'.str_replace('/','_',$data);
	}

	public function cacheOrFetch($url,$data)
	{
		$cache=$this->cacheFile($data);
		error_log($cache);
		if (file_exists($cache)) return file_get_contents($cache);
		error_log("missing($cache)");
		return $this->fetchAndCache($url,$data);
	}

	public function fetchAndCache($url,$data)
	{
		$page=$this->fetchPage($url);
		if (!is_null($page)) {
			file_put_contents($this->cacheFile($data),$page);
		}
		return $page;
	}

	/**
	 * Получить и распарсить данные из URL
	 * @param $url - url
	 * @param $data - код wiki которому соответствует URL (comp:5)
	 * @param $name - имя домены
	 * @param $empty - текст для отсутствия страницы
	 * @param $cache - можно взять из кэша
	 * @return mixed|string|string[]|null
	 */
	public function fetchAndParse($url,$data,$name=null,$empty=null,$cache=true)
	{
		if (is_array($data)) $data=implode('.',$data);
		$page=$cache?$this->cacheOrFetch($url,$data):$this->fetchAndCache($url,$data);
		return $this->parsePage($page,$name,$empty);
	}

	/**
	 * Сюда передаем распарсенные данные из синтаксиса
	 * отвечаем уже отрендеренными HTML данными
	 * @param $data
	 * @param string|null $name замена имени в элементе
	 * @param bool $cache
	 * @return false|string|string[]
	 */
	public function fetchInventory($data,$name=null,$cache=true) {
		$controller=$data[0];
		$id=$data[1];
		$method=isset($data[2])?($data[2]):'item';
		$api=$this->api;
		switch ($controller) {
			case 'comp':
			case 'os':
				if (is_numeric($id)) {
					return $this->fetchAndParse($api.'/comps/item?id='.$id,$data,$name,null,$cache);
				} else {
					return $this->fetchAndParse($api.'/comps/item-by-name?name='.$id,$data,$name,null,$cache);
				}

			case 'ip':
			case 'ips':
				return $this->fetchAndParse($api.'/net-ips/item-by-name?name='.urlencode($id),$data,$name,$id,$cache);

			case 'net':
			case 'network':
				return $this->fetchAndParse($api.'/networks/item-by-name?name='.urlencode($id),$data,$name,$id,$cache);

			case 'org-phone':
			case 'org-phones':
				if (is_numeric($id)) {
					return $this->fetchAndParse($api.'/'.$controller.'/item?id='.$id,$data,$name,null,$cache);
				} return 'Поддерживается ссылка только через ID';

			case 'segment':
			case 'segments':
				switch ($method) {
					case 'list':
						return $this->fetchAndParse($api.'/segments/list',$data,null,null,$cache);
					default: return 'ОШИБКА: Неправильный синтаксис ссылки на сегменты '.$method;
				}

			case 'service':
				switch ($method) {
                    case 'support':
                        return $this->fetchAndParse($api.'/services/card-support?id='.$id,$data,null,null,$cache);
                    case 'maintenance-req':
                    case 'maintenance-reqs':
                        return $this->fetchAndParse($api.'/services/card-maintenance-reqs?id='.$id,$data,null,null,$cache);
					case 'item':
						if (empty($id)) return '<a href="'.$api.'/services/">Укажите номер сервиса в инвентаризации</a>' ;
						return $this->fetchAndParse($api.'/services/'.$method.'?id='.$id,$data,$name,null,$cache);
					default: return 'ОШИБКА: Неизвестный элемент сервиса';
				}

			case 'user':
				if (is_numeric($id)) {
					return $this->fetchAndParse($api.'/users/item?id='.$id,$data,$name,null,$cache);
				} elseif (strpos($id,' ')===false) {
					return $this->fetchAndParse($api.'/users/item-by-login?login='.$id,$data,$name,null,$cache);
				} else {
					return $this->fetchAndParse($api.'/users/item-by-name?name='.urlencode($id),$data,$name,null,$cache);
				}

			case 'tech_model':
				if (is_numeric($id)) {
					return $this->fetchAndParse($api.'/tech-models/item?id='.$id.'&long=1',$data,$name,null,$cache);
				} else {
					$tokens=explode('/',$id);
					if (count($tokens)!=2) return 'ОШИБКА: не удалось определить производителя/модель';
					return $this->fetchAndParse($api.'/tech-models/item-by-name?name='.urlencode($tokens[1]).'&manufacturer='.urlencode($tokens[0]).'&long=1',$data,$name,null,$cache);
				}

            case 'tech':
                if (is_numeric($id)) {
                    return $this->fetchAndParse($api.'/techs/item?id='.$id,$data,$name,null,$cache);
                } else {
                    return $this->fetchAndParse($api.'/techs/item-by-name?name='.urlencode($id),$data,$name,null,$cache);
                }

            case 'maintenance_reqs':
            case 'maintenance_req':
                if (empty($id)) {
                    return $this->fetchAndParse($api.'/maintenance-reqs/list',$data,$name,null,$cache);
                } else if (is_numeric($id)) {
                    return $this->fetchAndParse($api.'/maintenance-reqs/item?id='.$id,$data,$name,null,$cache);
                } else {
                    return $this->fetchAndParse($api.'/maintenance-reqs/item-by-name?name='.urlencode($id),$data,$name,null,$cache);
                }

            default:
				return 'ОШИБКА: неизвестный тип объекта';
		}
	}
}

