<!-- wp:template-part {"slug":"sowing-calendar"} /-->
<div class="vs-calendar summary">
	<table class="sowing-calendar">
		<thead>
			<tr class="">
				<th class="calendar-label">Month</th>
				<th class="calendar-label--month" title="January">J</th>
				<th class="calendar-label--month" title="February">F</th>
				<th class="calendar-label--month" title="March">M</th>
				<th class="calendar-label--month" title="April">A</th>
				<th class="calendar-label--month" title="May">M</th>
				<th class="calendar-label--month" title="June">J</th>
				<th class="calendar-label--month" title="July">J</th>
				<th class="calendar-label--month" title="August">A</th>
				<th class="calendar-label--month" title="September">S</th>
				<th class="calendar-label--month" title="October">O</th>
				<th class="calendar-label--month" title="November">N</th>
				<th class="calendar-label--month" title="December">D</th>
			</tr>
		</thead>
		<tbody>
			<?php
			if (!empty($args['sowing_row'])) {
			?>
				<tr class="">
					<td class="calendar-label">Sow</td>
					<?php
					echo $args['sowing_row'];
					?>
				</tr>
			<?php
			}

			if (!empty($args['transplant_row'])) {
			?>
				<tr class="">
					<td class="calendar-label">Transplant</td>
					<?php
					echo $args['transplant_row'];
					?>
				</tr>
			<?php
			}

			if (!empty($args['harvest_row'])) {
			?>
				<tr class="">
					<td class="calendar-label">Harvest</td>
					<?php
					echo $args['harvest_row'];
					?>
				</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>