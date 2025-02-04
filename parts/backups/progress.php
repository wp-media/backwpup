<?php
use BackWPup\Utils\BackWPupHelpers;

$job_object = BackWPup_Job::get_working_data();
$abort_url = '';
$lobfiledata = $logfiledata ?? '';
if (current_user_can('backwpup_jobs_start') && is_object($job_object)) {
//read existing logfile
    $logfiledata = file_get_contents($job_object->logfile);
    preg_match('/<body[^>]*>/si', $logfiledata, $match);
    if (!empty($match[0])) {
        $startpos = strpos($logfiledata, $match[0]) + strlen($match[0]);
    } else {
        $startpos = 0;
    }
    $endpos = stripos($logfiledata, '</body>');
    if (empty($endpos)) {
        $endpos = strlen($logfiledata);
    }
    $length = strlen($logfiledata) - (strlen($logfiledata) - $endpos) - $startpos;
    $abort_url = wp_nonce_url(network_admin_url('admin.php') . '?page=backwpupjobs&action=abort', 'abort-job');
}

BackWPupHelpers::component("heading", [
  "level" => 1,
  "title" => __("We are creating a backup of your site…", 'backwpup'),
  "class" => "max-md:text-center",
  "identifier" => "backupgeneration-progress-box-title",
]);
?>

<?php BackWPupHelpers::component("progress-box", [
    "class" => "backupgeneration-progress-box",
    "abortUrl" => $abort_url,
]); ?>
<input type="hidden" name="logpos" id="logpos" value="<?php echo strlen($logfiledata ?? ''); ?>" />
<input type="hidden" name="next_job_id" id="next_job_id" value="" />
<div id="tb-showworking" style="display:none;">
  <div id="showworking"><?php echo substr($logfiledata ?? '', $startpos ?? 0, $length ?? 0); ?></div>
</div>
