<?php
namespace dashboard\MyWidgets\Simplidents;
use JobRouter\Api\Dashboard\v1\Widget;

class Simplidents extends Widget{
	
	
    public function getTitle(){
        return 'Aktuelle Vorgaenge';
    }
	
	public function getDimensions() {

        return [
            'minHeight' => 2,
            'minWidth' => 2,
            'maxHeight' => 4,
            'maxWidth' => 2,
        ];
    }

    public function isAuthorized(){
        $JobDB = $this->getJobDB();
        $query = "SELECT * 
                  FROM simplifyWidgets
                  WHERE id = 0";
        $result = $JobDB->query($query);

        while($row = $JobDB->fetchRow($result)){
            $jobFunction = $row['jobfunction'];
        }	
        
        return $this->getUser()->isInJobFunction($jobFunction);
    }

    public function getData(){
        return [
            'incidents' => $this->getIncidents(),
            'labels' => $this->getLabels(),
            'process' => $this->getAllProcess()
        ];
    }

    public function getAllProcess(){
        $JobDB = $this->getJobDB();
        $query = "SELECT processname 
                  FROM simplifyWidgets
                  WHERE id != 0";
        $result = $JobDB->query($query);
        
        $allProcess = [];
        while($row = $JobDB->fetchRow($result)){
            $allProcess[] = $row['processname'];
        }
        return json_encode($allProcess);
    }

    public function getLabels()
    {
        $JobDB = $this->getJobDB();

        $query = "SELECT * 
                  FROM simplifyWidgets
                  WHERE id = 1";
        $result = $JobDB->query($query);

        $labels = [];
        while($row = $JobDB->fetchRow($result)){
            $labels = explode(",", $row['steplabel']);
        }	
        array_unshift($labels, "Total");
        return json_encode($labels);
    }
	
	public function getIncidents(){
        $selected = $this->getSelectedProcess("1");
        $steps = $this->getSteps();
        $stepsString = implode(",", $steps);
        $JobDB = $this->getJobDB();
        $temp = "SELECT step, COUNT(step) AS STEP_COUNT, steplabel
                 FROM JRINCIDENTS
                 WHERE processname = '" .$selected['processname'] ."' " ."AND step IN (" .$stepsString .")
                 AND (STATUS = 0 OR STATUS = 1)
                 GROUP BY step";
        $result = $JobDB->query($temp);

        $incidents = array_fill(0, count($steps), 0);
        while($row = $JobDB->fetchRow($result)){
            for($i = 0; $i < count($steps); $i++){
                if($row["step"] == $steps[$i]){
                    $incidents[$i] = $row["STEP_COUNT"];
                }
            } 
        }	
        array_unshift($incidents, (string)array_sum($incidents));
	    return json_encode($incidents);
    }

    public function getSelectedProcess($id){
        $JobDB = $this->getJobDB();

        $query = "SELECT * FROM simplifyWidgets WHERE id = " .$id;
        $result = $JobDB->query($query);

        $row = $JobDB->fetchRow($result);
        return $row;
    }

    public function getSteps()
    {
        $selected = $this->getSelectedProcess("1");
        $labels = explode(",", $selected["steplabel"]);

        $JobDB = $this->getJobDB();

        $steps = [];

        for($i = 0; $i < count($labels); $i++){
            $query = "SELECT step FROM JRINCIDENTS WHERE processname = '" . $selected["processname"] . "' AND steplabel = '" .$labels[$i] ."' GROUP BY STEP";
            $result = $JobDB->query($query);
            while ($row = $JobDB->fetchRow($result)) {
                array_push($steps, $row["step"]);
            }
        }
        
        return $steps;
    }
}
		