<?php  
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/
if (! defined ( 'DIR_CORE' )) {
	header ( 'Location: static_pages/' );
}
class ControllerBlocksBannerBlock extends AController {
	
	public function main() {

        //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

		$instance_id = func_get_arg(0);
		$block_data = $this->getBlockContent($instance_id);
		$this->view->assign('block_framed',$block_data['block_framed']);
		$this->view->assign('content',$block_data['content']);
    	$this->view->assign('heading_title', $block_data['title'] );
    	$this->view->assign('stat_url', $this->html->getURL('r/extension/banner_manager') );

		if($block_data['content']){
			// need to set wrapper for non products listing blocks
			if($this->view->isTemplateExists($block_data['block_wrapper'])){
				$this->view->setTemplate( $block_data['block_wrapper'] );
			}
			$this->processTemplate();
		}
        //init controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
  	}

	protected function getBlockContent($instance_id) {
		$block_info = $this->layout->getBlockDetails($instance_id);
		$custom_block_id = $block_info['custom_block_id'];
		$descriptions = $this->layout->getBlockDescriptions($custom_block_id);
		if($descriptions[$this->config->get('storefront_language_id')]){
			$key = $this->config->get('storefront_language_id');
		}else{
			$key = key($descriptions);
		}

		$this->loadModel('extension/banner_manager');
		$results = $this->model_extension_banner_manager->getBanners($custom_block_id);
		if($results){
			$rl = new AResource('image');
			foreach($results as $row){
				if($row['banner_type']==1){ // if graphic type
					$images = $rl->getResourceAllObjects('banners',$row['banner_id']);
					if($images){
						$row['images'] = $images;
					}
				}else{
					$row['description'] = html_entity_decode($row['description']);
				}
				$banners[] = $row;
				$this->model_extension_banner_manager->writeBannerStat($row['banner_id']);
			}
		}
		$output = array(
			'title' => $descriptions[$key]['title'],
			'content' => $banners,
			'block_wrapper' => $descriptions[$key]['block_wrapper'],
			'block_framed' => (int)$descriptions[$key]['block_framed'],
		);

		return $output;
	}
}
?>