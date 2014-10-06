<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>


<div class="tab-content">
	<div class="col-sm-12 col-lg-12">
		<ul class="content-nav">
			<li><?php echo $select_range;?></li>
		</ul>
	</div>
	<div class="panel-body panel-body-nopadding">
		<div id="report" style="width: 700px; height: 480px; margin: auto;"></div>
	</div>
</div>



<!--[if IE]>
<script type="text/javascript" src="<?php echo RDIR_TEMPLATE; ?>javascript/jquery/flot/excanvas.js"></script>
<![endif]-->
<script type="text/javascript" src="<?php echo RDIR_TEMPLATE; ?>javascript/jquery/flot/jquery.flot.js"></script>
<script type="text/javascript"><!--
function getSalesChart(range) {
	$.ajax({
		type: 'GET',
		url: '<?php echo $chart_url; ?>&range=' + range,
		dataType: 'json',
		async: false,
		success: function(json) {
			var option = {
				shadowSize: 0,
				lines: {
					show: true,
					fill: true,
					lineWidth: 1
				},
				grid: {
					backgroundColor: '#FFFFFF'
				},
				xaxis: {
            		ticks: json.xaxis,
					axisLabel: json.xaxisLabel
				},
				yaxis: {
            		axisLabel: '<?php echo $text_count; ?>'
				}
			}

			$.plot($('#report'), [json.viewed, json.clicked], option);

		}
	});
}
getSalesChart($('#range').val());

$('#range').change(function(){
	getSalesChart($(this).val());
});
//--></script>
