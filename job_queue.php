<?php

// php page: job_queue.php

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
require_once("./inc/JobDescription.inc");

session_start();

$queue = new JobQueue();

if (isset($_GET['exited'])) {
  $_SESSION['user']->logout();
  session_unset();
  session_destroy();
  header("Location: " . "login.php"); exit();
}

if (!isset($_SESSION['user']) || !$_SESSION['user']->isLoggedIn()) {
  header("Location: " . "login.php"); exit();
}

if (isset($_SERVER['HTTP_REFERER']) && !strstr($_SERVER['HTTP_REFERER'], 'job_queue')) {
  $_SESSION['referer'] = $_SERVER['HTTP_REFERER'];
}

if (isset($_POST['delete'])) {
  if (isset($_POST['jobs_to_kill'])) {
    $queue->markJobsAsRemoved($_POST['jobs_to_kill']);
  }
}
else if (isset($_POST['update']) && $_POST['update']=='update') {
  // nothing to do
}
// TODO remove
/*else if (isset($_POST['OK']) && $_POST['OK'] == 'OK' && isset($_SESSION['referer'])) {
  header("Location: " . $_SESSION['referer']); exit();
}*/

$meta = "<meta http-equiv=\"refresh\" content=\"10\" />";

$script = "queue.js";

include("header.inc.php");

?>

    <div id="nav">
        <ul>
            <li><a href="select_images.php?exited=exited">exit</a></li>
            <li><a href="javascript:openWindow('http://support.svi.nl/wiki/style=hrm&amp;help=HuygensRemoteManagerHelpQueue')">help</a></li>
        </ul>
    </div>
    
    <div id="joblist">
    
        <div id="queuecontrols">
        
<?php

$referer = $_SESSION['referer'];

?>

            <input type="button" name="back" value="" class="icon back" onclick="document.location.href='<?php echo $referer ?>'" />
        
        </div>
        
        <h3>Job Queue Status</h3>
        
        <form method="post" action="" id="jobqueue">
        
            <div id="input">
            
<?php

if ($_SESSION['user']->name() != "admin")  {

?>
                <p>You can delete queued jobs owned by yourself.</p>
<?php

}

?>

                <p>
                    <input name="update" type="submit" value="" class="icon update" />
<?php

echo "                    ".date("l d F Y, H:i:s")."\n";

?>
                </p>
                
            </div> <!-- input -->
            
            <div id="queue">
            
                <table>
                
                    <tr>
                        <td class="del"></td>
                        <td class="nr">nr</td>
                        <td class="owner">owner</td>
                        <td class="files">file(s)</td>
                        <td class="creted">created</td>
                        <td class="status">status</td>
                        <td class="started">started</td>
                        <td class="pid">pid</td>
                        <td class="server">server</td>
                    </tr>
<?php

$rows = $queue->getContents();
if (count($rows) == 0) {
  echo "                    <tr style=\"background: #ffffcc\"><td colspan=\"9\">The job queue is empty</td></tr>";
}
else {
  $index = 1;
  foreach ($rows as $row) {
    if ($row['status'] == "started") {
      $color='#99ffcc';
    }
    else if ($row['status'] == "broken") {
      $color='#ff9999';
    }
    else if ($index % 2 == 0) {
      //$color='#f3cba5';
      $color='#ffccff';
    }
    else {
      //$color='#11d6ff';
      $color='#ccccff';
    }

?>
                    <tr style="background: <?php echo $color ?>">
<?php

    if ($row['username'] == $_SESSION['user']->name() || $_SESSION['user']->name() == "admin") {
      if ($row['status'] != "started" && $row['status'] != "broken") {

?>
                            <td><input name="jobs_to_kill[]" type="checkbox" value="<?php echo $row['id'] ?>" /></td>
<?php

      }
      else {

?>
                        <td></td>
<?php

      }
    }
    else {

?>
                        <td></td>
<?php

    }

?>
                        <td><?php echo $index ?></td>
                        <td><?php echo $row['username'] ?></td>
                        <td><?php echo implode(';', $queue->getJobFilesFor($row['id'])) ?></td>
                        <td><?php echo $row['queued'] ?></td>
                        <td><?php echo $row['status'] ?></td>
                        <td><?php echo $row['start'] ?></td>
                        <td><?php echo $row['process_info'] ?></td> 
                        <td><?php echo $row['server'] ?></td> 		
                    </tr>
<?php

    $index++;
  }
}

?>
                </table>
                
<?php

if (count($rows) != 0) {
    // <input name="jobs_to_kill[]" type="checkbox" value="45a4bd343e852" />

?>
                <label style="padding-left: 3px">
                    <img src="images/arrow.png" alt="arrow" />
                    <a href="javascript:mark()">Check All</a> / <a href="javascript:unmark()">Uncheck All</a>
                </label>
                
                &nbsp;
                
                <label style="font-style: italic">
                    With selected:
                    <input name="delete" type="submit" value="" class="icon delete" />
                </label>
<?php

}

?>

            </div> <!-- queue -->
            
        </form>
        
    </div> <!-- joblist -->
    
<?php

include("footer.inc.php");

?>
