<?php

class ControllerExtensionModuleDeltayml extends Controller {
 
  public function index() {
	//ini_set('error_reporting', E_ALL);
	//ini_set('display_errors', 1);
	//ini_set('display_startup_errors', 1);
    // Загружаем "модель" модуля
	$this->load->model('extension/module');
    $this->load->model('extension/module/deltayml');
	$this->load->model('catalog/category');
	$this->load->language('extension/module/category');
	$this->load->model('setting/setting');
	$this->load->model('catalog/product');
	$categories = $this->model_extension_module_deltayml->getCategory();
	$data['entry_filepath'] = '';
	foreach ($categories as $key=>$category) {
		$data['filter_category'] = $category['category_id'];
		$products = $this->model_extension_module_deltayml->getProducts($data);
		foreach ($products as $j=>$product) {
			$products[$j]['attributes'] = $this->model_extension_module_deltayml->getProductAttributes($product['product_id']);
			$products[$j]['options'] = $this->model_extension_module_deltayml->getProductOptions($product['product_id']);
			$products[$j]['href'] = $this->url->link('catalog/product', '?product_id=' . $product['product_id']);
		}
		$categories[$key]['products'] = $products;
	}
	$data = array();
	$data['categories'] = $categories;
	$filepath = '';
	$data['error_warning'] = '';
	
    // Сохранение настроек модуля, когда пользователь нажал "Создать"
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      // Вызываем метод "модели" для сохранения настроек
	  
	  if ($this->request->post['savePath']) {
		$fpath = $this->request->post['savePath'];
	  } else {
		$fpath = '';
	  }
      
	  // Метод формирования yml файла 
	  $filepath = $this->Uploadyml($fpath);
	  
	  if ($filepath != '' && isset($filepath)){
		  
		  $this->model_extension_module_deltayml->SaveSettings($filepath);
		  // Выходим из настроек с выводом сообщения
		  
		  $this->session->data['success'] = 'Файл сформирован!';
		  $this->response->redirect($this->url->link('extension/module/deltayml', 'token=' . $this->session->data['token'] .'&type=module', true));
		  $data['entry_filepath'] = $filepath;
	  } else {
		$data['entry_filepath'] = '';
		$this->session->data['error_warning'] = 'Ошибка формирования файла!';
		$data['error_warning'] = 'Ошибка формирования файла!';
		$this->response->redirect($this->url->link('extension/module/deltayml', 'token=' . $this->session->data['token'] .'&type=module', true));
	  }
    }
 
    // Загружаем настройки через метод "модели"

    $data['module_deltayml_filepath'] = $this->model_extension_module_deltayml->LoadSettings();
    // Загружаем языковой файл
    $data += $this->load->language('extension/module/deltayml');
    // Загружаем "хлебные крошки"
    $data += $this->GetBreadCrumbs();
 
    // Кнопки действий
    $data['action'] = $this->url->link('extension/module/deltayml', 'token=' . $this->session->data['token'] .'&action=data_file', true);
    $data['cancel'] = $this->url->link('marketplace/extension', 'token=' . $this->session->data['token'] .'&type=module', true);
    // Загрузка шаблонов для шапки, колонки слева и футера
    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');
	$data['text_edit'] = $this->language->get('text_edit');

    // Выводим в браузер шаблон
    $this->response->setOutput($this->load->view('extension/module/deltayml', $data));
 
  }
  
  // формирование yml
  public function Uploadyml($filepath = '') {
	
	
	$this->load->model('extension/module');
	$this->load->model('extension/module/deltayml');
	
	$data_file = $this->getlist();
	// Записываем данные в файл yml
	if (isset($data_file) && !empty($data_file)) {
		
		//$this->load->model('setting/setting');
		if ($filepath != '') {
			$filename = $_SERVER['DOCUMENT_ROOT'].'/'.$filepath.'/YML-'.date("d-m-Y-H-i").'.yml';
		} else {
			$filename = $_SERVER['DOCUMENT_ROOT'].'/system/storage/YML-'.date("d-m-Y-H-i").'.yml';
		}

		
		$yml = '<?xml version="1.0" encoding="UTF-8"?><yml_catalog date="'.date('Y-m-d').'T'.date('H:i:s').'+03:00"><shop>';
		$yml .= '<name>'.$this->config->get('config_meta_title').'</name>';
		$yml .= '<company>ИП Барышников Е.А.</company><platform>OpenCart 3</platform><version>1.0</version><agency>Технологичные решения</agency>';
		$yml .= '<email>'.$this->config->get('config_email').'</email>';
		$yml .= '<currencies><currency id="RUR" rate="1"/></currencies>';
		$yml .= '<categories>';
		
			foreach ($data_file['categories'] as $res=>$result):
			echo '<pre>'.print_r($result['name'],1).' Идёт добавление...</pre>';
				if ($result['parent_id']):
					$yml .= '<category id="'.$result["category_id"].'" parentId="'.$result["parent_id"].'">'.$result["name"].'</category>';
				else:
					$yml .= '<category id="'.$result["category_id"].'">'.$result["name"].'</category>';
				endif;
			endforeach;
		
		$yml .= '</categories>';
		
		// offers
		$yml .= '<offers>';
		$host = $_SERVER['HTTP_HOST'];
        $protocol=$_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
		$url_path = $protocol.'://'.$host.'/';

		
		foreach ($data_file['categories'] as $cat):
			if ($cat['products']){
				foreach($cat['products'] as $product):
					$yml .= '<offer id="'.$product['product_id'].'">';
						$yml .= '<name>'.$product['name'].'</name>';
						$yml .= '<vendor>'.html_entity_decode($product['name']).'</vendor>';
						$yml .= '<vendorCode>'.$product['model'].'</vendorCode>';
						$yml .= '<url>'.$url_path.''.$product['keyword'].'</url>';
						$yml .= '<price>'.intval($product['price']).'</price>';
						$yml .= '<currencyId>RUR</currencyId>';
						$yml .= '<categoryId>'.$product['category_id'].'</categoryId>';
						$yml .= '<delivery>false</delivery>';
						$yml .= '<pickup>false</pickup>';
						$yml .= '<store>true</store>';
						$yml .= '<description>'.html_entity_decode($product['description']).'</description>';
						$yml .= '<manufacturer_warranty>true</manufacturer_warranty>';
						$yml .= '<barcode>'.$product['product_id'].'</barcode>';
						if (count($product['attributes']) > 0):
							foreach ($product['attributes'] as $attr):
								$yml .= '<param id='.$attr['attribute_id'].'>'.$attr['text'].'</param>';
							endforeach;
						endif;
						$yml .= '<weight>'.$product['weight'].'</weight>';
						$yml .= '<dimensions>'.intval($product['price']).'/'.intval($product['price']).'</dimensions>';
						$yml .= '<count>'.$product['quantity'].'</count>';
					$yml .= '</offer>';
				endforeach;
			}
		endforeach;
		
		$yml .= '</offers>';
		
		$yml .= '</shop></yml_catalog>';

		$file = file_put_contents($filename,$yml);
				
		if ($file) {
			$filepath = $file;
		}
		
	} 
	
	return $filepath;
  }
 
  // Хлебные крошки
  private function GetBreadCrumbs() {
    $data = array(); $data['breadcrumbs'] = array();
    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/dashboard', '', true)
    );
    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_extension'),
      'href' => $this->url->link('marketplace/extension', '?type=module', true)
    );
    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/module/deltayml','', true)
    );
    return $data;
  }
  
  // Берём данные о категориях и товарах
  public function getlist() {
	$this->load->model('extension/module');
    $this->load->model('extension/module/deltayml');
	$this->load->model('catalog/category');
	$this->load->model('catalog/product');
	$this->load->model('tool/image');
	
	$categories = $this->model_extension_module_deltayml->getCategory();

	$url = '';
	
	// Формируем данные
	$data_file = [];
	
	foreach ($categories as $key=>$category) {
		$data['filter_category'] = $category['category_id'];
		$products = $this->model_extension_module_deltayml->getProducts($data);
		foreach ($products as $j=>$product) {
			$products[$j]['attributes'] = $this->model_extension_module_deltayml->getProductAttributes($product['product_id']);
			$products[$j]['options'] = $this->model_extension_module_deltayml->getProductOptions($product['product_id']);
			$products[$j]['href'] = $this->url->link('product/product', '?product_id=' . $product['product_id'] . $url);
		}
		$categories[$key]['products'] = $products;
	}
	
	$data_file['categories'] = $categories;
	
	/*
	
		foreach ($categories as $key=>$category) {
			$data['filter_category'] = $category['category_id'];
			
			$data_file['categories'][$key] = array(
				'name' => $category['name'],
				'href' => $this->url->link('product/category', $category['category_id']);
			);
			
			$products = $this->model_extension_module_deltayml->getProducts($data);

			foreach ($products as $j=>$product):
					if ($product['image']) {
						$image = $this->model_tool_image->resize($product['image'], $this->config->get($this->config->get('config_theme') . '_image_product_width'), $this->config->get($this->config->get('config_theme') . '_image_product_height'));
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', $this->config->get($this->config->get('config_theme') . '_image_product_width'), $this->config->get($this->config->get('config_theme') . '_image_product_height'));
					}
					
					$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

					if ((float)$product['special']) {
						$special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					} else {
						$special = false;
					}

					if ($this->config->get('config_tax')) {
						$tax = $this->currency->format((float)$product['special'] ? $product['special'] : $product['price'], $this->session->data['currency']);
					} else {
						$tax = false;
					}

					if ($this->config->get('config_review_status')) {
						$rating = (int)$product['rating'];
					} else {
						$rating = false;
					}
			
				$data_file['categories'][$key]['products'][$j] = array(
						'product_id'  => $product['product_id'],
						'thumb'       => $image,
						'name'        => $product['name'],
						'description' => utf8_substr(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get($this->config->get('config_theme') . '_product_description_length')) . '..',
						'price'       => $price,
						'special'     => $special,
						'tax'         => $tax,
						'minimum'     => ($product['minimum'] > 0) ? $product['minimum'] : 1,
						'rating'      => $rating,
						'href'        => $this->url->link('product/product', '?product_id=' . $product['product_id'] . $url)
				);
			endforeach;
	
		}
	*/
	
	return $data_file;
	
  }
 
}

?>