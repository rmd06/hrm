<?php

// php page: task_parameter.php

// This file is part of huygens remote manager.

// Copyright: Montpellier RIO Imaging (CNRS)

// contributors :
// 	     Pierre Travo	(concept)
// 	     Volker Baecker	(concept, implementation)

// email:
// 	pierre.travo@crbm.cnrs.fr
// 	volker.baecker@crbm.cnrs.fr

// Web:     www.mri.cnrs.fr

// huygens remote manager is a software that has been developed at 
// Montpellier Rio Imaging (mri) in 2004 by Pierre Travo and Volker 
// Baecker. It allows running image restoration jobs that are processed 
// by 'Huygens professional' from SVI. Users can create and manage parameter 
// settings, apply them to multiple images and start image processing 
// jobs from a web interface. A queue manager component is responsible for 
// the creation and the distribution of the jobs and for informing the user 
// when jobs finished.

// This software is governed by the CeCILL license under French law and 
// abiding by the rules of distribution of free software. You can use, 
// modify and/ or redistribute the software under the terms of the CeCILL 
// license as circulated by CEA, CNRS and INRIA at the following URL 
// "http://www.cecill.info".

// As a counterpart to the access to the source code and  rights to copy, 
// modify and redistribute granted by the license, users are provided only 
// with a limited warranty and the software's author, the holder of the 
// economic rights, and the successive licensors  have only limited 
// liability.

// In this respect, the user's attention is drawn to the risks associated 
// with loading, using, modifying and/or developing or reproducing the 
// software by the user in light of its specific status of free software, 
// that may mean that it is complicated to manipulate, and that also 
// therefore means that it is reserved for developers and experienced 
// professionals having in-depth IT knowledge. Users are therefore encouraged 
// to load and test the software's suitability as regards their requirements 
// in conditions enabling the security of their systems and/or data to be 
// ensured and, more generally, to use and operate it in the same conditions 
// as regards security.

// The fact that you are presently reading this means that you have had 
// knowledge of the CeCILL license and that you accept its terms.

require_once("./inc/User.inc");
require_once("./inc/Parameter.inc");
require_once("./inc/Setting.inc");

session_start();


if (isset($_GET['exited'])) {
  $_SESSION['user']->logout();
  session_unset();
  session_destroy();
  header("Location: " . "login.php"); exit();
}

if (!isset($_SESSION['user']) || !$_SESSION['user']->isLoggedIn()) {
  header("Location: " . "login.php"); exit();
}

if (!isset($_SESSION['task_setting'])) {
  # session_register("task_setting"); 
  $_SESSION['task_setting'] = new TaskSetting();
}
if ($_SESSION['user']->name() == "admin") $_SESSION['task_setting']->setNumberOfChannels(5);
else $_SESSION['task_setting']->setNumberOfChannels($_SESSION['setting']->numberOfChannels());

$message = "            <p class=\"warning\">&nbsp;<br />&nbsp;</p>\n";

$parameter = $_SESSION['task_setting']->parameter("FullRestoration");

// TODO refactor code to consider only full restorations
if ($parameter->value() == "False") {
  
  $parameter->setValue("True");
  $_SESSION['task_setting']->set($parameter);
  
  $parameter = $_SESSION['task_setting']->parameter("RemoveBackground");
  $parameter->setValue("False");
  $_SESSION['task_setting']->set($parameter);
  
}
else {

  // TODO refactor code to never consider remove noise
  $parameter = $_SESSION['task_setting']->parameter("RemoveNoise");
  
  if (isset($_POST['RemoveNoise'])) {
    $parameter->setValue("True");
  }
  else {
    $parameter->setValue("False");
  }
  $_SESSION['task_setting']->set($parameter);
  
  $names = $_SESSION['task_setting']->parameterNames();
  foreach ($names as $name) {
    $parameter = $_SESSION['task_setting']->parameter($name);
    if ($name != "NumberOfIterations" && isset($_POST[$name])) {
      $parameter->setValue($_POST[$name]);
      $_SESSION['task_setting']->set($parameter);
    }
    /*else {
      $value = $parameter->value();
      if ($parameter->isBoolean() && isset($_POST['OK'])) {
        $parameter->setValue("False");
        $_SESSION['task_setting']->set($parameter);
      }
    }*/
  }

  if (isset($_POST["DeconvolutionAlgorithm"]))
      $algorithm = strtoupper($_POST["DeconvolutionAlgorithm"]);
  else {
      $algorithmValue = $_SESSION['task_setting']->parameter("DeconvolutionAlgorithm")->value();
      if ($algorithmValue != null)
        $algorithm = strtoupper($algorithmValue);
      else
        $algorithm = "CMLE";
  }
  
  $backgroundOffsetPercentParam =  $_SESSION['task_setting']->parameter("BackgroundOffsetPercent");
  $backgroundOffset = $backgroundOffsetPercentParam->internalValue();
  for ($i = 0; $i < $_SESSION['task_setting']->numberOfChannels(); $i++) {
    $signalNoiseRatioKey = "SignalNoiseRatio".$algorithm.$i;
    $backgroundOffsetKey = "BackgroundOffsetPercent".$i;
    if (isset($_POST[$signalNoiseRatioKey])) {
      // enable ranges for the signal to noise ratio
      $value = $_POST[$signalNoiseRatioKey];
      $val = explode(" ", $value);
      if (count($val) > 1) {
        $values = array(NULL, NULL, NULL, NULL);
        for ($j = 0; $j < count($val); $j++) {
          $values[$j] = $val[$j];
        }
        $signalNoiseRatioRange[$i] = $values;
      }
      else {
        $signalNoiseRatio[$i] = $_POST[$signalNoiseRatioKey];
        //echo $signalNoiseRatio[$i]."<br />";
      }
    }
    if (isset($_POST[$backgroundOffsetKey])) {
      $backgroundOffset[$i] = $_POST[$backgroundOffsetKey];
    } 
  }
  $parameter = $_SESSION["task_setting"]->parameter("SignalNoiseRatioUseRange");
  
  if (isset($signalNoiseRatioRange) && count($signalNoiseRatioRange) > 0) {
    // << ECHO
    /*for ($i = 0; $i < count($signalNoiseRatioRange); $i++) {
      $range = $signalNoiseRatioRange[$i];
      for ($j = 0; $j < count($range); $j++) {
        $val = $range[$j];
        if ($val == NULL) $val = "NULL";
        echo "signalNoiseRatioRange, channel ".$i." value ".$j." = ".$val."<br>";
      }
    }*/
    $parameter->setValue("True");
    $signalNoiseRatioRangeParam = $_SESSION['task_setting']->parameter("SignalNoiseRatioRange");
    for ($i = 0; $i < $_SESSION['task_setting']->numberOfChannels(); $i++) {
      if ($signalNoiseRatioRange[$i] == NULL) {
        $signalNoiseRatioRange[$i] = array($signalNoiseRatio[$i], NULL, NULL, NULL);
        //echo "value " . $signalNoiseRatio[$i] . " added in array for channel " . $i;
      }
    }
    $signalNoiseRatioRangeParam->setValue($signalNoiseRatioRange);
    $_SESSION['task_setting']->set($signalNoiseRatioRangeParam);
  }
  else if (count($_POST) > 0) {
    $parameter->setValue("False");
    $signalNoiseRatioParam = $_SESSION['task_setting']->parameter("SignalNoiseRatio");
    $signalNoiseRatioParam->setValue($signalNoiseRatio);
    $_SESSION['task_setting']->set($signalNoiseRatioParam);
  }
  $_SESSION["task_setting"]->set($parameter);
  $backgroundOffsetPercentParam->setValue($backgroundOffset);
  $_SESSION['task_setting']->set($backgroundOffsetPercentParam);
  
  if (isset($_POST['BackgroundEstimationMode']) && $_POST['BackgroundEstimationMode'] == "auto") {
    $parameter = $_SESSION['task_setting']->parameter("BackgroundOffsetPercent");
    $parameter->setValue("auto");
    $_SESSION['task_setting']->set($parameter);
  }
  else if (isset($_POST['BackgroundEstimationMode']) && $_POST['BackgroundEstimationMode'] == "object") {
    $parameter = $_SESSION['task_setting']->parameter("BackgroundOffsetPercent");
    $parameter->setValue("object");
    $_SESSION['task_setting']->set($parameter);
  }
  
  /*
  $signalNoiseRatioRangeParam = $_SESSION['task_setting']->parameter("SignalNoiseRatioRange");
  $backgroundOffsetRangeParam = $_SESSION['task_setting']->parameter("BackgroundOffsetRange");
  $numberOfIterationsRangeParam = $_SESSION['task_setting']->parameter("NumberOfIterationsRange");
  $signalNoiseRatioRange = $signalNoiseRatioRangeParam->value();
  $backgroundOffsetRange = $backgroundOffsetRangeParam->value();
  $numberOfIterationsRange = $numberOfIterationsRangeParam->value();
  for ($i=0; $i < 4; $i++) {
    $signalNoiseRatioRangeKey = "SignalNoiseRatioRange{$i}";
    if (isset($_POST[$signalNoiseRatioRangeKey])) {
      $signalNoiseRatioRange[$i] = $_POST[$signalNoiseRatioRangeKey];
    }
    $backgroundOffsetRangeKey = "BackgroundOffsetRange{$i}";
    if (isset($_POST[$backgroundOffsetRangeKey])) {
      $backgroundOffsetRange[$i] = $_POST[$backgroundOffsetRangeKey];
    } 
    $numberOfIterationsRangeKey = "NumberOfIterationsRange{$i}";
    if (isset($_POST[$numberOfIterationsRangeKey])) {
      $numberOfIterationsRange[$i] = $_POST[$numberOfIterationsRangeKey];
    } 
  }
  $signalNoiseRatioRangeParam->setValue($signalNoiseRatioRange);
  $backgroundOffsetRangeParam->setValue($backgroundOffsetRange);
  $numberOfIterationsRangeParam->setValue($numberOfIterationsRange);
  $_SESSION['task_setting']->set($signalNoiseRatioRangeParam);
  $_SESSION['task_setting']->set($backgroundOffsetRangeParam);
  $_SESSION['task_setting']->set($numberOfIterationsRangeParam);
  */
  // number of iterations: set the use of range to false if checkbox is unchecked
  /*$parameter = $_SESSION["task_setting"]->parameter("NumberOfIterationsUseRange");
  if (isset($_POST["OK"]) && !isset($_POST["NumberOfIterationsUseRange"])) {
        $parameter = $_SESSION["task_setting"]->parameter("NumberOfIterationsUseRange");
        $parameter->setValue("False");
        $_SESSION["task_setting"]->set($parameter);
  }*/
  // enable ranges for the number of iterations
  if (isset($_POST["NumberOfIterations"])) {
    $value = $_POST["NumberOfIterations"];
    $values = explode(" ", $value);
    if (count($values) > 1) {
      $parameter = $_SESSION["task_setting"]->parameter("NumberOfIterationsUseRange");
      $parameter->setValue("True");
      $_SESSION["task_setting"]->set($parameter);
      $numberOfIterationsRangeParam = $_SESSION['task_setting']->parameter("NumberOfIterationsRange");
      $numberOfIterationsRange = $numberOfIterationsRangeParam->value();
      //$numberOfIterationsRange = array(NULL, NULL, NULL, NULL);
      for ($i = 0; $i < count($values); $i++) {
        $numberOfIterationsRange[$i] = $values[$i];
      }
      $numberOfIterationsRangeParam->setValue($numberOfIterationsRange);
      $_SESSION['task_setting']->set($numberOfIterationsRangeParam);
    }
    else {
      $parameter = $_SESSION["task_setting"]->parameter("NumberOfIterationsUseRange");
      $parameter->setValue("False");
      $_SESSION["task_setting"]->set($parameter);
      $parameter = $_SESSION['task_setting']->parameter("NumberOfIterations");
      $parameter->setValue($value);
      $_SESSION['task_setting']->set($parameter);
    }
  }
  
  if (isset($_POST['QualityChangeStoppingCriterion'])) {
    $parameter = $_SESSION['task_setting']->parameter("QualityChangeStoppingCriterion");
    $parameter->setValue($_POST['QualityChangeStoppingCriterion']);
    $_SESSION['task_setting']->set($parameter);
  }
  
  if (isset($_POST['DeconvolutionAlgorithm'])) {
    $parameter = $_SESSION['task_setting']->parameter("DeconvolutionAlgorithm");
    $parameter->setValue($_POST['DeconvolutionAlgorithm']);
    $_SESSION['task_setting']->set($parameter);
  }
  
  if (count($_POST) > 0) {
    $ok = $_SESSION['task_setting']->checkParameter();
    $message = "            <p class=\"warning\">".$_SESSION['task_setting']->message()."</p>\n";
    if ($ok) {
      $saved = $_SESSION['task_setting']->save();			
      $message = "            <p class=\"warning\">".$_SESSION['task_setting']->message()."</p>\n";
      if ($saved) {
        header("Location: " . "select_task_settings.php"); exit();
      }
    }	 
  }

}

$noRange = False;

$script = "settings.js";

include("header.inc.php");

?>
    <!--
      Tooltips
    -->
    <span id="ttSpanCancel">Abort editing and go back to the Restoration parameters selection page. All changes will be lost!</span>  
    <span id="ttSpanForward">Save your settings.</span>
    <?php if ($estimateSNR) { ?>
    <span id="ttEstimateSnr">Use a sample raw image to find a SNR estimate for each channel.</span>
    <?php } ?>
    
    <div id="nav">
        <ul>
            <li><?php echo $_SESSION['user']->name(); ?></li>
            <li><a href="javascript:openWindow('http://support.svi.nl/wiki/style=hrm&amp;help=HuygensRemoteManagerHelpRestorationParameters')"><img src="images/help.png" alt="help" />&nbsp;Help</a></li>
        </ul>
    </div>
    
    <div id="content">
    
        <h3>Task Setting</h3>
        
        <form method="post" action="" id="select">
          
           <h4>How should your images be restored?</h4>
           
             <fieldset class="setting">  <!-- deconvolution algorithm -->
            
                <legend>
                    <a href="javascript:openWindow('http://support.svi.nl/wiki/RestorationMethod')"><img src="images/help.png" alt="?" /></a>
                    deconvolution algorithm
                </legend>

<?php

$onChange = "onChange=\"javascript:switchSnrMode()\"";

?>
                <select name="DeconvolutionAlgorithm" <?php echo $onChange ?>>
                
<?php

$parameter = $_SESSION['task_setting']->parameter("DeconvolutionAlgorithm");
$possibleValues = $parameter->possibleValues();
$selectedValue  = $parameter->value();

// This restores the default behavior in case the entry "DeconvolutionAlgorithm"
// is not in the database
if ( empty( $possibleValues ) == true )
{
  $possibleValues[0] = "cmle";
  $parameter = $_SESSION['task_setting']->parameter("DeconvolutionAlgorithm");
  $parameter->setValue( "cmle" );
  $_SESSION['task_setting']->set($parameter);
}
  
foreach($possibleValues as $possibleValue) {
  $translation = $_SESSION['task_setting']->translation("DeconvolutionAlgorithm", $possibleValue);
  // This restores the default behavior in case the entry "DeconvolutionAlgorithm"
  // is not in the database
  if ( $translation == false )
    $translation = "cmle";

  if ( $possibleValue == $selectedValue ) {
      $option = "selected=\"selected\"";
  } else {
      $option = "";
  }
?>
                    <option <?php echo $option?> value="<?php echo $possibleValue?>"><?php echo $translation?></option>
<?php
}
?>
                </select>
                
            </fieldset>
        
            <fieldset class="setting">  <!-- signal/noise ratio -->
            
                <legend>
                    <a href="javascript:openWindow('http://support.svi.nl/wiki/style=hrm&amp;help=SignalToNoiseRatio')"><img src="images/help.png" alt="?" /></a>
                    signal/noise ratio
                </legend>

<?php

$parameter = $_SESSION["task_setting"]->parameter("SignalNoiseRatioUseRange");
if ($parameter->isTrue()) {
  $signalNoiseRatioRangeParam = $_SESSION['task_setting']->parameter("SignalNoiseRatioRange");
  $signalNoiseRatioRange = $signalNoiseRatioRangeParam->value();
}
else {
  $signalNoiseRatioParam = $_SESSION['task_setting']->parameter("SignalNoiseRatio");
  $signalNoiseRatioValue = $signalNoiseRatioParam->value();
}

?>
                <div id="snr">
                      
<?php

$visibility = " style=\"display: none\"";
if ($selectedValue == "cmle")
  $visibility = " style=\"display: block\"";

?>
                    <div id="cmle-snr" class="multichannel"<?php echo $visibility?>>
                    <ul>
                      <li>SNR: 
                      <div class="multichannel">
<?php

for ($i = 0; $i < $_SESSION['task_setting']->numberOfChannels(); $i++) {
  
  if ($parameter->isTrue()) {
    $signalNoiseRatioValues = $signalNoiseRatioRange[$i];
    $value = $signalNoiseRatioValues[0];
    for ($j = 1; $j < count($signalNoiseRatioValues); $j++){
      if ($signalNoiseRatioValues[$j])
        $value .= " " . $signalNoiseRatioValues[$j];
    }
  }
  else {
    $value = "";
    if ($selectedValue == "cmle")
        $value = $signalNoiseRatioValue[$i];
  }

?>
                          <span class="nowrap">Ch<?php echo $i ?>:<span class="multichannel"><input name="SignalNoiseRatioCMLE<?php echo $i ?>" type="text" size="8" value="<?php echo $value ?>" class="multichannelinput" /></span>&nbsp;</span>
<?php

}

?>
                          </div>
                        </li>
                      </ul>

                    <?php
                    if ($estimateSNR) {
                        echo "<a href=\"estimate_snr_from_image.php\"
                          onmouseover=\"TagToTip('ttEstimateSnr' )\"
                          onmouseout=\"UnTip()\"
                        ><img src=\"images/calc_small.png\" alt=\"\" />";
                        echo "Estimate SNR from image</a>";
                    }

                    ?>
                    </div>
<?php

$visibility = " style=\"display: none\"";
if ($selectedValue == "qmle")
  $visibility = " style=\"display: block\"";

?>
                    <div id="qmle-snr" class="multichannel"<?php echo $visibility?>>
                      <ul>
                        <li>SNR:
                        <div class="multichannel">
<?php

for ($i = 0; $i < $_SESSION['task_setting']->numberOfChannels(); $i++) {

?>
                        <span class="nowrap">Ch<?php echo $i ?>:
                            <select name="SignalNoiseRatioQMLE<?php echo $i ?>">
<?php

  for ($j = 1; $j <= 4; $j++) {
      $option = "                                <option ";
      if (isset($signalNoiseRatioValue)) {
          if ($signalNoiseRatioValue[$i] >= 1 && $signalNoiseRatioValue[$i] <= 4) {
            if ($j == $signalNoiseRatioValue[$i])
                $option .= "selected=\"selected\" ";
          }
          else {
              if ($j == 2)
                $option .= "selected=\"selected\" ";
          }
      }
      else {
          if ($j == 2)
            $option .= "selected=\"selected\" ";
      }
      $option .= "value=\"".$j."\">";
      if ($j == 1)
        $option .= "low</option>";
      else if ($j == 2)
        $option .= "fair</option>";
      else if ($j == 3)
        $option .= "good</option>";
      else if ($j == 4)
        $option .= "inf</option>";
      echo $option;
  }

?>
                            </select>
                        </span>
<?php

}

?>
                          </div>
                        </li>
                      </ul>
                    </div>
                    
                </div>
                
            </fieldset>
            
            <fieldset class="setting">  <!-- background mode -->
            
                <legend>
                    <a href="javascript:openWindow('http://support.svi.nl/wiki/style=hrm&amp;help=BackgroundMode')"><img src="images/help.png" alt="?" /></a>
                    background mode
                </legend>
                
                <div id="background">
                
<?php

$backgroundOffsetPercentParam =  $_SESSION['task_setting']->parameter("BackgroundOffsetPercent");
$backgroundOffset = $backgroundOffsetPercentParam->internalValue();

$flag = "";
if ($backgroundOffset[0] == "" || $backgroundOffset[0] == "auto") $flag = " checked=\"checked\"";

?>

                    <input type="radio" name="BackgroundEstimationMode" value="auto"<?php echo $flag ?> />automatic background estimation<p />
                    
<?php

$flag = "";
if ($backgroundOffset[0] == "object") $flag = " checked=\"checked\"";

?>

                    <input type="radio" name="BackgroundEstimationMode" value="object"<?php echo $flag ?> />in/near object<p />
                    
<?php

$flag = "";
if ($backgroundOffset[0] != "" && $backgroundOffset[0] != "auto" && $backgroundOffset[0] != "object") $flag = " checked=\"checked\"";

?>
                    <input type="radio" name="BackgroundEstimationMode" value="manual"<?php echo $flag ?> />
                    remove constant absolute value:
                    
                    <div class="multichannel">
<?php

for ($i=0; $i < $_SESSION['task_setting']->numberOfChannels(); $i++) {
  $val = "";
  if ($backgroundOffset[0] != "auto" && $backgroundOffset[0] != "object") $val = $backgroundOffset[$i];

?>
                        <span class="nowrap">Ch<?php echo $i ?>:<span class="multichannel"><input name="BackgroundOffsetPercent<?php echo $i ?>" type="text" size="8" value="<?php echo $val ?>" class="multichannelinput" /></span>&nbsp;</span>
                        
<?php

}

?>
                    </div>
                    
                </div>
                
            </fieldset>
            
            <fieldset class="setting">  <!-- stopping criteria -->
            
                <legend>
                    stopping criteria
                </legend>
                
                <div id="criteria">
                
                    <a href="javascript:openWindow('http://support.svi.nl/wiki/style=hrm&amp;help=MaxNumOfIterations')"><img src="images/help.png" alt="?" /></a>
                    number of iterations:
                    
<?php

$parameter = $_SESSION['task_setting']->parameter("NumberOfIterations");
$value = 40;
if ($parameter->value() != NULL) {
  $value = $parameter->value();
}

$parameter = $_SESSION["task_setting"]->parameter("NumberOfIterationsUseRange");
if ($parameter->isTrue()) {
  $numberOfIterationsRangeParam = $_SESSION['task_setting']->parameter("NumberOfIterationsRange");
  $numberOfIterationsRange = $numberOfIterationsRangeParam->value();
  $value = $numberOfIterationsRange[0];
  for ($i = 1; $i < 4; $i++){
    if ($numberOfIterationsRange[$i] != NULL)
      $value .= " " . $numberOfIterationsRange[$i];
  }
}

?>
                    <input name="NumberOfIterations" type="text" size="8" value="<?php echo $value ?>" />
                    
                    <p />
                    
                    <a href="javascript:openWindow('http://support.svi.nl/wiki/style=hrm&amp;help=QualityCriterion')"><img src="images/help.png" alt="?" /></a>
                    quality change:
                    
<?php

$parameter = $_SESSION['task_setting']->parameter("QualityChangeStoppingCriterion");
$value = 0.1;
if ($parameter->value() != null) {
  $value = $parameter->value();
}

?>
                    <input name="QualityChangeStoppingCriterion" type="text" size="3" value="<?php echo $value ?>" />
                    
                </div>
                
            </fieldset>
            
            <div><input name="OK" type="hidden" /></div>
            
            <div id="controls">
              <input type="button" value="" class="icon up"
                  onmouseover="TagToTip('ttSpanCancel' )"
                  onmouseout="UnTip()"
                  onclick="document.location.href='select_task_settings.php'" />
              <input type="submit" value="" class="icon save"
                  onmouseover="TagToTip('ttSpanForward' )"
                  onmouseout="UnTip()"
                  onclick="process()" />
            </div>

        </form>
        
    </div> <!-- content -->
    
    <div id="rightpanel">
    
      <div id="info">
          
        <h3>Quick help</h3>

        <p>On this page you specify the parameters for restoration.</p>
        
        <p>These parameters comprise the deconvolution algorithm, the
        estimation of the SNR of the images, the mode for background
        estimation, and the stopping criteria.</p>
        
        <p>The 'Estimate SNR from image' tool allows you to obtain an
        estimation of the SNR of your images to be used with the
        'Classic Maximum Lilelihood Estimation' algorithm.</p>
            
        <p>The first stopping criterium reached, will stop the restoration.</p>
        
      </div>
        
      <div id="message">
<?php

echo $message;

?>
        </div>
        
    </div> <!-- rightpanel -->
    
<?php

include("footer.inc.php");

?>
