<?php

//Based on algorithm shown here
//https://www.youtube.com/watch?v=4oDLMs11Exs
//(c) Sabu Francis, April 5, 2021


$AllActivities=[];

class Activity {

     public $name;
     public $duration;
     public $earlyStart;
     public $earlyFinish;
     public $lateStart;
     public $lateFinish;
     public $predes;
     public $nexts;
     public $lastNode;

     function __construct($nm,$dur){
          $this->name=$nm;
          $this->duration=$dur;
          $this->predes=[];
          $this->nexts=[];
          $this->earlyFinish = -1;
          $this->earlyStart = -1;
          $this->lateFinish = -1;
          $this->lateStart = -1;




     }

    public function reportUsingTemplate($Template){
        $replacements = array(
             "N"  =>  $this->name,
             "D"  =>  $this->duration,
             "ES" =>  $this->earlyStart,
             "EF" =>  $this->earlyFinish,
             "LS" =>  $this->lateStart,
             "LF" =>  $this->lateFinish,
             "PR" =>  implode(",",$this->predes)
        );

        foreach($replacements as $key => $val) {
          $Template = str_replace("[$key]",$val,$Template);
        }

       return $Template;

     }



     public function isOnCP(){
          return ($this->lateFinish == $this->earlyFinish) ;

     }



     public function getName(){
          return $this->name;
     }

     public function addPre($pre) {
           array_push($this->predes,$pre);

     }
     public function addNext($nxt) {
        array_push($this->nexts,$nxt);

  }

     public function countPre(){
           return count($this->predes);
     }


    public function setEarly(){

          if($this->earlyFinish != -1) return;
          if($this->earlyStart == -1) {
               foreach($this->predes as $objNm){
                getActivityObj($objNm)->setEarly();
               }
          }
          switch(count($this->predes)){
             case 0: $this->earlyStart = 0;
                     $this->earlyFinish = $this->earlyStart + $this->duration;
                     break;
             case 1: $Prev = getActivityObj($this->predes[0]);
                     if($Prev!=false) {
                      $va= $Prev->getEarlyFinish();
                      $this->earlyStart = $va['earlyFinish'];
                      $this->earlyFinish = $this->earlyStart + $this->duration;
                     }
                     break;
              default:
                    $arrs=getPrevEarlyFinishes($this->predes);
                    $Prev=getBiggestEarlyFinish($arrs);
                    $va= $Prev->getEarlyFinish();
                    $this->earlyStart = $va['earlyFinish'];
                    $this->earlyFinish = $this->earlyStart + $this->duration;
                    break;

          }


    }


    public function setLates(){


        if($this->lateStart != -1) return;
        if($this->lateFinish == -1) {
             foreach($this->nexts as $objNm){
              getActivityObj($objNm)->setLates();
             }
        }


        switch(count($this->nexts)){
           case 0:
                    $this->lateFinish = $this->earlyFinish;
                    $this->lateStart = $this->lateFinish - $this->duration;
                    break;

          case 1:
                    $Next = getActivityObj($this->nexts[0]);
                    if($Next!=false) {
                     $va= $Next->getLateStart();
                     $this->lateFinish = $va['lateStart'];
                     $this->lateStart = $this->lateFinish - $this->duration;
                    }
                    break;

            default:
                  $arrs=getPrevLateStarts($this->nexts);
                  $Prev=getSmallestLateStart($arrs);
                  $va= $Prev->getLateStart();
                  $this->lateFinish = $va['lateStart'];
                  $this->lateStart = $this->lateFinish - $this->duration;
                  break;

        }


  }



  public function getEarlyFinish(){
    if ($this->earlyFinish != -1)
       return array("name"=>$this->name,
                   "earlyFinish"=>$this->earlyFinish
                   );
     else {
           $this->setEarly();
           return array("name"=>$this->name,
                        "earlyFinish"=>$this->earlyFinish
                        );
         }

     }


     public function getLateStart(){
        if ($this->lateStart != -1)
           return array("name"=>$this->name,
                       "lateStart"=>$this->lateStart
                       );
         else {
               $this->setLates();
               return array("name"=>$this->name,
                            "lateStart"=>$this->lateStart
                            );
             }

         }


 }

function getBiggestEarlyFinish($arrs){
    $ef = array_column($arrs, 'earlyFinish');
    array_multisort($ef, SORT_DESC, $arrs);
    $objnm=$arrs[0]['name'];
    return getActivityObj($objnm);

}

function getSmallestLateStart($arrs){
    $ef = array_column($arrs, 'lateStart');
    array_multisort($ef, SORT_ASC, $arrs);
    $objnm=$arrs[0]['name'];
    return getActivityObj($objnm);

}



function getPrevEarlyFinishes($acts){
    $arr= array();
    foreach($acts as $activityNm){

      array_push($arr,getActivityObj($activityNm)->getEarlyFinish());
    }
    return $arr;
}

function getPrevLateStarts($acts){
    $arr= array();
    foreach($acts as $activityNm){

      array_push($arr,getActivityObj($activityNm)->getLateStart());
    }
    return $arr;
}




function checkIfPreviousStartAbsent($predes){
    global $AllActivities;
    if(!empty($predes)) return true;
    foreach($AllActivities as $activity){
        if(empty($activity->predes )) {
            //that means some other activity already has empty predecessors
            //Empty predecssor means there was a start node!
            return false;
        }
    }

    return true;

}

function addActivity($Objname,$duration,$preceds){
    global $AllActivities;
    $isFine = checkIfPreviousStartAbsent($preceds);
    if(!$isFine) return false;

    $activity = new Activity($Objname,$duration);

      foreach($preceds as $p)
       $activity->addPre($p);

    array_push($AllActivities,$activity);



}

function getActivityObj($objname){
     global $AllActivities;
    foreach($AllActivities as $activity ){
         if($activity->getName()==$objname){
             return $activity;
         }
    }
}

function getRootActivity(){
     global $AllActivities;
     $possible ='';
     $cou = 0;
     foreach($AllActivities as $activity ){
        if($activity->countPre()==0){
           $possible =  $activity;
           $cou++;
          }
     }

     //Only one node can have such a condition
     //That it never had any precedent
     if($cou == 1) return $possible;
      else return false;

}

function setForwards(){
    global $AllActivities;
    foreach($AllActivities as $activity ){
        $nm  = $activity->getName();
        foreach($AllActivities as $act2 ){
            if($act2->getName() == $nm) continue;
            else {
            $preds = $act2->predes;
            if(in_array($nm,$preds)){

                     $activity->addNext($act2->getName());

               }
            }
        }
    }
}

function setFinalStopNode(){
    global $AllActivities;
    $nms = array();
    foreach($AllActivities as $activity ){
        array_push($nms,$activity->getName());
            }
    foreach($AllActivities as $activity ){
       $nms = array_values(array_diff($nms,$activity->predes));

         }


    if(count($nms)== 1) {
        $lastObj= getActivityObj($nms[0]);
        return $lastObj;

     } else return false;


}

function explainPMDiagram($Temp){
    global $AllActivities;
    $str= '';
    foreach($AllActivities as $Activity){
        $str .= $Activity->reportUsingTemplate($Temp);
    }

    return $str;
}


function calculatePMDiagram($Template1, $Template2){
        global $AllActivities;
        setForwards();
        $lastObj= setFinalStopNode();
        if(!$lastObj) {
           // echo "Sorry, the network is incorrect. Has multiple final endings";
            return 1;
        }

        $lastObj->setEarly();
        $firstObj = getRootActivity();
        if(!$firstObj) {
            //echo "Sorry, the network is incorrect. Has multipe start points";
            return 2;
        }

        $firstObj->setLates();

        $str= '';
        foreach($AllActivities as $Activity){
            $Temp = $Template1;
            if($Activity->isOnCP()) $Temp = $Template2;
            $str .= $Activity->reportUsingTemplate($Temp);



        }

        return $str;

    }

function cleanActivities(){
    global $AllActivities;
    $AllActivities = array();
}

/* 
//Example usage 
cleanActivities();
addActivity("A",3,[]);
addActivity("B",4,["A"]);
addActivity("C",2,["A"]);
addActivity("D",5,["B"]);
addActivity("E",1,["C"]);
addActivity("F",2,["C"]);
addActivity("G",4,["D","E"]);
addActivity("H",3,["F","G"]);

echo explainPMDiagram("Name: [N], Duration: [D], Predecessors: [PR]\n")."\n";


$Template1 ="Name: [N], Duration: [D], Early Start: [ES], Early Finish: [EF], Late Start: [LS], Late Finish: [LF]\n";
$Template2 = "*$Template1";
$ret = calculatePMDiagram($Template1,$Template2);
 if(intval($ret) == 0) echo $ret;
 else
  {
   switch($ret) {
     case 1: echo "Sorry, the network is incorrect. Has multiple final endings"; break;
     case 2: echo "Sorry, the network is incorrect. Has multipe start points"; break;
   }

  }
*/
