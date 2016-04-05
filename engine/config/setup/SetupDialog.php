<?php
DEFINE('TEMP_CONFIG_FILE', __DIR__ . '/temp-config-data.json');
DEFINE('CONFIG_FILE', __DIR__ . '/../config-data.json');
class SetupDialog extends Page{
	public function getName(){
		return "Welcome";
	}
	public function getURL(){
		return "/";
	}
	public function isMatch($URL){
		return preg_match("/^(.*)$/", $URL);
	}
	public function run($template){
		if(file_exists(TEMP_CONFIG_FILE)){
			Engine::getConfig()->setConfigFilename(TEMP_CONFIG_FILE);
			Engine::getConfig()->open();
		}
	}
	public function show($template){
		require('views/header.php');
		$chapters = $this->getAllChaptersHTML();
		$slides = new Element("slides", null, $chapters);
		echo $slides->toHTML();
		require('views/footer.php');
	}
	private $allChapters;
	public function __construct($allChapters){
		$this->allChapters = &$allChapters;
	}
	private function getAllChaptersHTML(){
		if(!is_null($this->allChapters)){
			$useChapters = array();
			foreach($this->allChapters as $chapter){
				if($chapter->isEnabled()){
					array_push($useChapters, $chapter);
				}
			}
			$sendChapters = array();
			foreach($useChapters as $key=>$chapter){
				if($chapter->isEnabled()){
					$chapter->SlideControls->elements = array();
					if($key == 0){
						//Show first Chapter
						$chapter->attributes = array('class'=>'active');
					}
					if ($key > 0){
						//Show Previous
						array_push($chapter->SlideControls->elements, new Element("input", array("type"=>"submit", "value"=>"Previous"), "&nbsp;"));
					}
					if ($key < count($useChapters) - 1){
						//Show Next
						array_push($chapter->SlideControls->elements, new Element("input", array("type"=>"submit", "value"=>"Next", "name"=>$chapter->getName()),null));
					} else {
						//Show Finish
						array_push($chapter->SlideControls->elements, new Element("input", array("type"=>"submit", "value"=>"Finish", "name"=>$chapter->getName()),null));
					}
					array_push($sendChapters, $chapter);
					//cho $chapter->toHTML();
				}
			}
			return $sendChapters;
		}
	}
}
?>