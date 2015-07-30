<div id="statesbox"> 
    <div class="statebox_table">
        <table>
            <tr>
				<td style="padding:20px 20px 20px 20px; text-align:left; vertical-align:top">
					<?php echo $icon; ?>
				</td>
				<td style="padding:0px 10px 0px 0px; text-align:left; vertical-align:middle">
					<h2><?php echo $message; ?></h2>
					<p><?php if (isset($content))
						echo $content; ?></p>
				</td>
            </tr>
        </table>
    </div>
</div>