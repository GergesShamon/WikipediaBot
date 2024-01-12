<?php
namespace Bot\Model;

use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;
use Exception;
class Stubs
{
    private $model;

    public function __construct() {
        $this->model = PersistentModel::load(new Filesystem(FOLDER_MODELS . "/Stubs.model"));
    }
    public function checkData(array $data, string $key) : void {
        if (!isset($data[$key])) {
            throw new Exception("$key does not exist.");
        }
        if (empty($data[$key])) {
            throw new Exception("$key is empty.");
        }
    }
    public function predict(array $data) : array {
        $predictions = [];
        
        foreach ($data as $_data){
            $this->checkData($_data, "title");
            $this->checkData($_data, "portals");
            $sample = array_fill(0, 25, "null");
    
            if( ! preg_match_all('/\w+/u', $_data["title"], $matches) ){
                throw new Exception("An error occurred in parsing title.");
            };
            $title = $matches[0];
            $categorys = explode(",", $_data["portals"]);
    
            for ($i = 0; $i < min(15, count($title)); $i++) {
                $sample[$i] = $title[$i];
            }
    
            for ($i = 0; $i < min(10, count($categorys)); $i++) {
                $sample[$i + count($title)] = $categorys[$i];
            }    
            $samples[] = $sample;
        }
    
        // Make predictions
        $newDataset = new Unlabeled($samples);
        $probabilities = $this->model->proba($newDataset);
        
        foreach ($probabilities as $key => $probabilitie) {
            $maxProbability = max($probabilitie);
            $predictedClass = array_search($maxProbability, $probabilitie);
    
            $predictions[] = [
                "title" => $data[$key]["title"],
                "stub" => $predictedClass,
                "probability" => $maxProbability
            ];
        }
    
        return $predictions;
    }

}
