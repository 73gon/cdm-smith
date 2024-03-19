<?PHP

require_once('../../../includes/central.php');

$JobDB = DBFactory::getJobDB();

$selectedOption = $_GET['selectedOption'];


$output = getAll($selectedOption);
echo json_encode($output);

function getAll($processname)
{
    $labels = getLabels($processname);
    $incidents = getIncidents($processname);
    $all = [
        'labels' => $labels,
        'incidents' => $incidents
    ];
    return $all;
}

function getLabels($processname)
{
    global $JobDB;

    $query = "SELECT * 
                FROM simplifyWidgets
                WHERE processname = '" .$processname ."'";
    $result = $JobDB->query($query);

    $labels = [];
    while($row = $JobDB->fetchRow($result)){
        $labels = explode(",", $row['steplabel']);
    }	
    array_unshift($labels, "Total");
    return json_encode($labels);
}

function getIncidents($processname){
    $selected = getSelectedProcess($processname);
    $steps = getSteps($processname);
    $stepsString = implode(",", $steps);
    global $JobDB;
    $temp = "SELECT step, COUNT(step) AS STEP_COUNT, steplabel
                FROM JRINCIDENTS
                WHERE processname = '" .$selected['processname'] ."' " ."AND step IN (" .$stepsString .")
                GROUP BY step";
    $result = $JobDB->query($temp);

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

function getSelectedProcess($processname){
    global $JobDB;

    $query = "SELECT * FROM simplifyWidgets WHERE processname = '" .$processname ."'";
    $result = $JobDB->query($query);

    $row = $JobDB->fetchRow($result);
    return $row;
}

function getSteps($processname)
{
    $selected = getSelectedProcess($processname);
    $labels = explode(",", $selected["steplabel"]);

    global $JobDB;

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

?>