<?php  
use BackWPup\Utils\BackWPupHelpers;  
  
/**  
 * @var int $job_id The job ID.  
 */  

if ( ! isset( $job_id ) ) {  
  return;
}
$name = BackWPup_Option::get($job_id, 'name');  
  
BackWPupHelpers::component("closable-heading", [  
  'title' => __("Backup now: ", 'backwpup') . $name,  
    'type' => 'modal'  
]);  
?>  
  
<?php  
BackWPupHelpers::component("alerts/info", [  
  "type" => "info",  
    "content" => __("Your backup will be created using the data and the storage location you selected for your scheduled backup.", 'backwpup'),  
]);  
?>  
  
<footer class="flex flex-col gap-2">  
  <?php  
  BackWPupHelpers::component("form/button", [  
  "type" => "primary",  
       "label" => __("Start", 'backwpup'),  
       "full_width" => true,  
       "trigger" => "start-backup-job",  
       "class" => "backwpup-start-backup-job",
       "data" => [ "job_id" => $job_id ],
    ]);  
    ?>  
</footer>