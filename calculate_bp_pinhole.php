<?php

// php page: calculate_bp_pinhole.php

// This file is part of huygens remote manager (HRM).

// Copyright Scientific Volume Imaging b.v., The Netherlands.
// Implemented by Jose Vi�a on 09/04/2005 for
// http://support.svi.nl/wiki/BackprojectedPinholeCalculator.
// Adapted by jose@svi.nl on December 2008 for the HRM.

// The purpose of this calculator is explained at
// http://support.svi.nl/wiki/BackprojectedPinholeCalculator

// huygens remote manager is a software that has been developed at 
// Montpellier Rio Imaging (mri) in 2004-2007 by Pierre Travo and Volker 
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

require_once ("./inc/User.inc");

session_start();

if (isset ($_GET['exited'])) {
    $_SESSION['user']->logout();
    session_unset();
    session_destroy();
    header("Location: " . "login.php");
    exit ();
}

if (!isset ($_SESSION['user']) || !$_SESSION['user']->isLoggedIn()) {
    header("Location: " . "login.php");
    exit ();
}


$params = array("d", "na", "wl", "mo", "msys");
$reportparams = array("micro", "d", "na", "wl", "mo", "msys", "c");



// What follows is a mini-database of microscope models with the necessary known
// parameters.
// Developers adding a new microscope model should see the usage of these
// parameters at
// http://support.svi.nl/wiki/index.php?edit=ReportOtherMicroscope
// It is not easy because originally this was not to be included in the HRM but
// online on the SVI server. Popular demand convinced us to include it embedded
// in the HRM but without much time to make this more programmer-friendly.
// Still it is easier than it looks, so please just read the linked
// instructions.
// Or even better, just report the new model to SVI and we will include online
// and in the HRM, for the benefit of all users:
// http://support.svi.nl/wiki/ReportOtherMicroscope

$microscopes = array (
    "Biorad MRC 500, 600 and 1024" =>
    array ("micro=Biorad_MRC_500_600_1024&param=Pinhole+diameter+(mm)&a=1&b=0&na=0&wl=0&msys=53.2&c=0.5&u=-3&cmsys=1&extra1=1.25&txt1=Fluorescence+attachment&extra2=1.25&txt2=DIC+attachment", "http://support.svi.nl/wiki/BioradMRC_500_600_1024"),
    "Biorad Radiance" =>
    array ("micro=Biorad_Radiance&param=Pinhole+diameter+(mm)&a=1&b=0&na=0&wl=0&msys=73.2&c=0.5&u=-3&cmsys=1&extra1=1.25&txt1=Fluorescence+attachment&extra2=1.25&txt2=DIC+attachment", "http://support.svi.nl/wiki/Biorad_Radiance"),
    "Leica confocal TCS 4d (parameter P_8)" =>
    array
    ("micro=Leica_TCS4d_P8&param=Reported+parameter+(P_8)&a=2.39216&b=20&na=0&wl=0&msys=4.5&c=0.56419&u=-6",
    "http://support.svi.nl/wiki/LeicaConfocal_TCS4d_SP1_NT"),
    "Leica confocals TCS 4d, SP1 and NT (Airy disk units)" =>
    array
    ("micro=Leica_TCS4d_SP1_NT_Airy_units&param=Number+of+Airy+disks&msys=0&mo=0&c=0.56419&a=0&b=0&u=0&wl=580",
    "http://support.svi.nl/wiki/LeicaConfocal_TCS4d_SP1_NT"),
    "Leica confocal SP2" => 
    array ("micro=Leica_TCS_SP2_Airy_units&param=Number+of+Airy+disks&msys=0&mo=0&c=0.56419&a=0&b=0&u=0&wl=580", "http://support.svi.nl/wiki/LeicaConfocal_TCS_SP2"),
    "Leica confocal SP5" => 
    array ("micro=Leica_TCS_SP5_Airy_units&param=Number+of+Airy+disks&msys=0&mo=0&c=0.56419&a=0&b=0&u=0&wl=580", "http://support.svi.nl/wiki/LeicaConfocal_TCS_SP5"),
    "Nikon TE2000-E with the C1 scanning head" =>
    array ("micro=Nikon_TE2000E_C1&param=Pinhole+diameter+(microns)&a=1&b=0&na=0&wl=0&msys=1&c=0.5&u=-6&extra1=1.5&txt1=Optional+1.5x+magnification",
    "http://support.svi.nl/wiki/Nikon_TE2000E_C1"),
    "Olympus FV300 and FVX" =>
    array( "micro=Olympus_FV300&param=Reported+pinhole+parameter&table=1&a=1&b=0&na=0&wl=0&msys=3.426&c=0.5&u=-6", "http://support.svi.nl/wiki/Olympus_FV300"),
    "Olympus FV500" =>
    array( "micro=Olympus_FV500&param=Pinhole+side+(microns)&a=1&b=0&na=0&wl=0&msys=3.8&c=0.5641896&u=-6", "http://support.svi.nl/wiki/Olympus_FV500"),
    "Olympus FV1000" =>
    array ("micro=Olympus_FV1000&param=Pinhole+side+(microns)&a=1&b=0&na=0&wl=0&msys=3.82&c=0.5641896&u=-6", "http://support.svi.nl/wiki/Olympus_FV1000"),
    "Yokogawa spinning disk (pinhole radius)" =>
    array ("micro=Yokogawa_spinning_disk&d=50&a=1&b=0&na=0&wl=0&msys=1&c=0.5&u=-6", 
    "http://support.svi.nl/wiki/YokogawaDisk"),
    "Yokogawa spinning disk (pinhole distance)" =>
    array ("micro=Yokogawa_disk_(pinhole_distance)&d=253&a=1&b=0&na=0&wl=0&msys=1&c=1&u=-6&ru=-6&rtag=pinhole+distance", 
    "http://support.svi.nl/wiki/BackProjectedPinholeDistance"),
    "Zeiss LSM410 inverted" =>
    array ("micro=Zeiss_LSM410_inverted_P8&param=Reported+parameter+(P_8)&a=3.92157&b=0&na=0&wl=0&msys=2.23&c=0.56419&u=-6", 
    "http://support.svi.nl/wiki/Zeiss_LSM410_inverted"),
    "Zeiss LSM510" =>
    array("micro=Zeiss_LSM510&param=Pinhole+diameter+(microns)&a=1&b=0&na=0&wl=0&msys=3.33&c=0.5&u=-6", "http://support.svi.nl/wiki/Zeiss_LSM510"),
    "Not listed" => 
    array ("micro=Not_listed_microscope&param=Pinhole+physical+diameter+(microns)&a=1&b=0&na=0&wl=0&u=-6", "http://support.svi.nl/wiki/ReportOtherMicroscope")
);



function globalize_vars ($var_string, $type) {
   global ${$type};
   if ($var_string && $type) {
      $var_name = trim(strtok ($var_string, ","));
      global ${$var_name};
      if (!isset(${$var_name}) && isset(${$type}["$var_name"]))
         ${$var_name} = ${$type}["$var_name"];
         #echo $var_name, $$var_name, " "; 
      while ($var_name) {
         $var_name = trim(strtok (","));
         global ${$var_name};
         if (!isset(${$var_name}) && isset(${$type}["$var_name"]) )
            ${$var_name} = ${$type}["$var_name"];
      }
   }
}


// Parameters passed by _GET or _POST that are dumped in global variables:
$post_string = "param, micro, d, a, b, c, u, wl, na, mo, msys, task, ref, ".
               "cmsys, extra1, extra2, table, txt1, txt2, checked1, checked2, ".
               "help, ru, rtag";
globalize_vars ($post_string, "_POST");
globalize_vars ($post_string, "_GET");

if (!isset($ref) || $ref =="" )
       $ref = "capturing_parameter.php";

if (!isset($ru) || $ru == "" ) $ru="-9";

// Units of the input parameter
switch($u) {
    case 0:
       $units = "(m)";
       break;
    case -3:
       $units = "(mm)";
       break;
    case -6:
       $units = "(&micro;m)";
       break;
    case -9:
       $units = "(nm)";
       break;
    default:
       $units = "";
       break;
}

// Units of the reported parameter
switch($ru) {
    case 0:
       $runits = "(m)";
       break;
    case -3:
       $runits = "(mm)";
       break;
    case -6:
       $runits = "(&micro;m)";
       break;
    case -9:
       $runits = "(nm)";
       break;
    default:
       $runits = "";
       break;
}


if (!isset($rtag) || $rtag == "" ) {
    $rtag="pinhole radius";
}

if ($param == "") $param = "Reported parameter $units";

$Label = array("micro" => "Microscope model",
                "d"     => $param,
                "wl"    => "Wavelength (nm)",
                "na"    => "Lens numerical aperture",
                "mo"    => "Objective magnification",
                "msys"  => "Internal system magnification",
                "c"     => "Shape factor"
                );

$Default = array("micro" => "Not specified",
                "d"     => "",
                "wl"    => "590",
                "na"    => "1.3",
                "mo"    => "100",
                "msys"  => "1",
                "c"     => "0.5"
                );




    function field($p)
    {

       global $Label, $WikiURL, $WikiArticle;

       #$string = " <a target=\"SviWiki\" href=\"".$WikiURL.$WikiArticle[$param]."\">";
       $string = $Label[$p];
       #$string .= "</a> ";
       return $string;
    }

    function fieldEntry($p)
    {

      global $Details, $Default;
      global $$p;

      if (isset($$p)) $val = $$p;
      else $val =  $Default[$p];

        $string = field($p);
        $string .= "<input type=\"text\" ".
              "name=\"$p\" size=\"5\" value=\"".
               $val."\">";
        #$string .= "<td>".$Details[$param]."</td>";
        return $string;
    }



# --

function start() {
    global $microscopes, $na;

   echo "\n<ul>";

    $script = $_SERVER['PHP_SELF'];


    foreach ($microscopes as $m => $data) {
        $extra = "";
        // If na is not defined in the model parameters take the one reported
        // from the HRM.
        if (!strstr($data[0], "na=" ) && isset($na) && $na != "") {
            $extra = "&na=$na";
        }
        echo "\n<li><a href=\"$script?$data[0]&help=$data[1]&task=form$extra\">$m</a></li>";
    }
    echo "\n</ul>";
    echo "\n</div> <!-- content -->\n";
    ?>
         <div id="stuff">

        <div id="info">

            <input type="button" value="" class="icon cancel" onclick="document.location.href='capturing_parameter.php'" />
            <?php 
            echo "<p>The following forms will assist you in calculating the ".
            "(circular-equivalent) backprojected pinhole radius expressed in ".
            "nanometers, that you can enter directly in the HRM settings.</p>";
            echo "<p>There is one special entry to calculate pinhole distances in spinning disks.</p>";
    echo "<p>Click <b>help</b> on the top menu for more details.</p>";    

    echo "<p>Start by selecting your microscope model from the list on the left, or click on cancel above to go back.</p>";
    echo "</div>";
    echo "</div>";



}

function form($error=false,$imglink="") {

global $params, $reportparams, $ref, $help, $rtag, $ru;
global $a, $b, $c, $d, $u, $param, $wl, $na, $mo, $msys, $micro, $units ;
global $cmsys, $table, $extra1, $txt1, $extra2, $txt2, $checked1, $checked2;


        if ($error) print $error . "<br><br>";
        print "\n<h2>".str_replace("_"," ",$micro)."</h2>";
        print "\n<form action=\"calculate_bp_pinhole.php\" method=\"post\"".
            " id=\"select\">";
        print "\n<fieldset class=\"setting\">";
        print "\nEnter the parameters as reported by your microscope:";

        print "\n<INPUT TYPE=\"hidden\" name=\"task\" value=\"calc\">";
        print "\n<INPUT TYPE=\"hidden\" name=\"ref\" value=\""
                                                       .$ref."\">";
        print "\n<INPUT TYPE=\"hidden\" name=\"param\" value=\""
                                                   .$param."\">";
        #print "\n<INPUT TYPE=\"hidden\" name=\"micro\" value=\""
                                                   #.$micro."\">";
        print "\n<INPUT TYPE=\"hidden\" name=\"a\" value=\""
                                                   .$a."\">";
        print "\n<INPUT TYPE=\"hidden\" name=\"b\" value=\""
                                                   .$b."\">";
     #   print "\n<INPUT TYPE=\"hidden\" name=\"c\" value=\""
                                                   #.$c."\">";
        print "\n<INPUT TYPE=\"hidden\" name=\"u\" value=\""
                                                   .$u."\">";
        print "\n<INPUT TYPE=\"hidden\" name=\"ru\" value=\""
                                                   .$ru."\">";
        print "\n<INPUT TYPE=\"hidden\" name=\"rtag\" value=\""
                                                   .$rtag."\">";
        print "\n<INPUT TYPE=\"hidden\" name=\"table\" value=\""
                                                   .$table."\">";
        print "\n<br><br>";
        $afterTable = "";

     foreach ($reportparams as $entry) {

        if (!isset($$entry) && $$entry !== "" && $$entry !== 0 
            || ($entry == "msys" && $cmsys==1) ) {
            print fieldEntry($entry)."<br>\n";
        }
        else
            $afterTable .= "\n<INPUT TYPE=\"hidden\" name=\"".$entry.
                                "\" value=\"".$$entry."\">";
     }

     if (isset($extra1) && isset($txt1)) {
         print (urldecode($txt1));
         print ("\n<input type=\"checkbox\" name=\"extra1\" value=\"$extra1\"".
                " $checked1><br>"); #checked
     }
     if (isset($extra2) && isset($txt2)) {
         print (urldecode($txt2));
         print ("\n<input type=\"checkbox\" name=\"extra2\" value=\"$extra2\"".
                " $checked2><br>"); #checked
     }
     print "<div><input name=\"OK\" type=\"hidden\" /></div>";


 
     print "\n$afterTable";

        # print "\n<input type=\"submit\" Value=\"Calculate\">";
        # print "<input type=\"submit\" value=\"\" class=\"icon apply\" onclick=\"process()\" />";

        print "\n</fieldset>";
        print "\n</form>";
?>  </div> <!-- content -->

         <div id="stuff">

        <div id="info">

            <input type="button" value="" class="icon cancel" onclick="document.location.href='capturing_parameter.php'" />
            <input type="submit" value="" class="icon apply" onclick="process()" />

            <p>
               Enter or confirm the requested values and press the ok button to calculate the
               <a href=\"javascript:openWindow('http://support.svi.nl/wiki/BackProjected')\">back projected</a> 
               pinhole radius.
               Press the cancel button to go back to the settings form.
            </p>
            <p>
               <?php
               print "Read more about the ".
               "<a href=\"javascript:openWindow('$help')\">$micro</a> model.";
               ?>
            </p>

       </div>

        <div id="message">

<?php

echo $message."\n\n       </div>\n\n    </div> <!-- stuff -->";



} # END form



# --

function serve() {

global $a, $b, $c, $d, $u, $param, $wl, $na, $mo, $msys, $micro;
global $extra1, $extra2, $table, $ru, $runits, $rtag;
global $ref, $params, $Label, $Default, $reportparams;

$warning = NULL; $out = ""; $error = "";

foreach ( $params as $entry) {
#    $out .= $entry. " ". $$entry."<br>\n";
    if (!isset($$entry) || $$entry == ""   || $$entry < 0) {
                    $$entry = $Default[$entry];
                    $warning .= "\n<br>Using default value ".$$entry." for ".$Label[$entry]." ".$entry;
    }
}


if ( ($mo == 0 || $msys == 0) && ($na == 0 && $wl ==0) ) {
    $error .= "Wrong magnification value.<br>\n";
} else {
    $M = $mo * $msys;
    if ($extra1) $M *= $extra1;
    if ($extra2) $M *= $extra2;
}

if ($c == 0) 
    $error .= "Wrong shape factor value.<br>\n";

if ($d == 0 || !isset($d)) 
    $error .= "Wrong pinhole value. Please enter a value as reported by your microscope.<br>\n";

if ($table == 1) {

    if ($micro == "Olympus_FV300") {

        switch($d) {
            case 1:
              $deff = 60;
              break;
            case 2:
              $deff = 100;
              break;
            case 3:
              $deff = 150;
              break;
            case 4:
              $deff = 200;
              break;
            case 5:
              $deff = 300;
              break;
            default:
              $error .= "Unknown size parameter";
              break;
        }
    }
    else $error .= "Undefined table for model $micro<br>\n";

} else {
    $deff = $d;
}
    #$out .= "d $deff";

if ($na != 0 && $wl != 0) {  # Reported in Airy disks

    $phr = $c * 1.22 * $wl * $deff / $na;
    $M = 1;  #Magnification doesn't apply in this case, don't divide at all.


} elseif ($a != 0) {

  if (!is_numeric($ru)) {
      $error .= "Wrong report units value $ru.<br>\n";
  }
  if (is_numeric($u) ) {
      $unitsf = pow(10,$u);
      $phr = pow(10,-1*$ru) * $unitsf * $c * ($a * $deff + $b);
      #$out .= "rb = ".pow(10,9) * $unitsf." * $c * ($a * $deff plus $b)<br>";
  }
  else
      $error .= "Wrong units value $u.<br>\n";


} else {
    $error .= "Wrong equation: not enough parameters.<br>\n";
}



       #print("<pre>");
#print("</pre>");

if ($error) {
    $out .="<h2>Error!</h2>\n".$error;
    /* $out .="<br>Please sent all the text above ".
                 "to the <a href = 'http://support.svi.nl/contact.php'>".
                 "system administrator</a><br>";
                 */
            }
        else
            {
            $result = round($phr / $M,2);    
            $out .= "<h3>Result</h3>";
        $out .=  "\nBackprojected $rtag $runits: <b>$result</b><br><br>";

            $out .= "\nThis is the parameter list used in this calculation:<br>";
            $out .= $warning;
 
             $out .= "\n<br>".field("micro").": ".$micro." ";
             #$out .= "\n<br>".field("msys").": ".$msys." ";
             foreach ( $reportparams as $entry ) {
                 if ($$entry !=0 && isset($$entry)) {
                   $out .= "\n<br>".field($entry).": ".$$entry." ";
                   if ($entry == "msys") {
                        if ($extra1) $out .= "&times;$extra1 ";
                        if ($extra2) $out .= "&times;$extra2 ";

                   }
                 }
             }
           $out .= "<br>\n";

        

      }

      /* $out = "<table width=\"60%\" border=\"0\" cellspacing=\"0\" ".
      "cellpadding=\"0\" align=\"center\"> <tr align=\"left\"> ".
      "<td>\n".$out.
      "</td> </tr> </table>\n"; */


      $out .= "  </div> <!-- content -->";

      $out .= "

               <div id=\"stuff\">
        <div id=\"info\">

            <p>
            On the left you can see the result of the calculation and the parameters used for it. Please annotate the calculated value and enter it in the parameter settings. 
            </p>
            <p>You can repeat the calculation with different input values (maybe for other channels) or proceed to the parameter settings form.
            </p>";

      $out .= "<p><a href=\"".$_SERVER['HTTP_REFERER']."\">Again (for other values)</a> - ";
      $out .= " <a href='$ref'>Continue</a></p>";

      $out .= "
       </div>

    </div> <!-- stuff -->";


      return $out;

} # END calc




# --

############ Start page
$script = "settings.js";

include("header.inc.php");
?>
<div id="nav">
        <ul>
            <li><a href="javascript:openWindow('http://support.svi.nl/wiki/BackprojectedPinholeCalculator')">help</a></li>
        </ul>
</div>
<?php
echo "<div id=\"content\"> ";

switch($task) {
    case 'calc':
        $html =  serve();
        echo $html;
        break;
    case 'form':    
        form();
        break;
    default:
        start();
# this case doesn't print html headers, it is intended to be called from
# a wiki article by inserting %%Nyquistcalculator%% there.
}
include ("footer.inc.php");

?>
