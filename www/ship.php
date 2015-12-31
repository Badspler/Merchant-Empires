<?php
/**
 * Popup page for ship information 
 *
 * @package [Redacted]Me
 * ---------------------------------------------------------------------------
 *
 * Merchant Empires by [Redacted] Games LLC - A space merchant game of war
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

	include_once('inc/page.php');
	include_once('inc/game.php');
	include_once('inc/cargo.php');
	include_once('inc/systems.php');

	$tmpl['no_fluff'] = true;
	$tmpl['page_title'] = 'Ship Information';

	include_once('tmpl/html_begin.php');

	$db = isset($db) ? $db : new DB;
?>
<div class="popup_spread">
	<div class="port_update_button">
		<a href="viewport.php" target="_top">
			<script type="text/javascript">drawButton('close', 'close', 'return true;');</script>
		</a>
	</div>
	<div class="header1">Player Ship</div>
	<div class="docs_text">
		Here you can manage various ship related things.
	</div>
	<hr />
	<div class="header2">Deploy Technology</div>
	<?php if ($spacegame['tech_count'] > 0) { ?>
		<div class="docs_text">
			Select a technology to deploy.
		</div>
		<?php

		$counter = 0;
		foreach ($spacegame['tech'] as $record_id => $tech) { 

			$deploy_amount = 1;
			$selectable_amount = false;
			$selectable_caption = false;

			?>

			<div class="header3">
				<img src="res/goods/<?php echo $spacegame['goods'][$tech['good']]['safe_caption']; ?>.png" width="24" height="24"  />
				<?php echo $spacegame['goods'][$tech['good']]['caption']; ?>
			</div>
			
			<div class="docs_text">
				<?php
					switch ($spacegame['goods'][$tech['good']]['safe_caption']) {
						case 'port_package':
							?>
							When deployed on a planetoid which does not already have one of these
							a new port is created. Random start goods will be added and it will be
							ready for trade.
							<?php
							$deploy_amount = 1;
							break;

						case 'shields':
						case 'armor':
							
							echo 'Deploying will replenish lost tech. You are carrying ';
							echo $tech['amount'] . ' ' . $spacegame['goods'][$tech['good']]['caption'] . '. ';

							$current_amount = $spacegame['player'][$spacegame['goods'][$tech['good']]['safe_caption']];
							$max_amount = $spacegame['ships'][$spacegame['player']['ship_type']][$spacegame['goods'][$tech['good']]['safe_caption']];

							if ($current_amount < $max_amount) {
								$deploy_amount = min($tech['amount'], $max_amount - $current_amount);
								echo 'You can replenish up to ' . $deploy_amount . ' units ';
								echo 'by clicking the following button. ';
							}
							else {
								$deploy_amount = 0;
								echo 'You do not need to replenish any ' . $spacegame['goods'][$tech['good']]['caption'];
							}

							break;

						case 'solar_collectors':
							
							echo 'When deployed on a star you will be able to collect ';
							echo 'energy. If another solar collector already exists this ';
							echo 'will increase its output. A maximum of ' . SOLAR_COLLECTORS_PER_SECTOR . ' ';
							echo 'collectors can be installed on any star.';
							
							$deploy_amount = 1;
							break;

						case 'drones':

							$x = $spacegame['player']['x'];
							$y = $spacegame['player']['y'];

							$own_count = 0;
							$total_count = 0;

							$rs = $db->get_db()->query("select owner, amount from ordnance where x = {$x} and y = {$y} and good = '34'");

							$rs->data_seek(0);
							while ($row = $rs->fetch_assoc()) {
								$total_count += $row['amount'];

								if ($spacegame['player']['record_id'] == $row['owner']) {
									$own_count += $row['amount'];
								}
							}

							echo 'A single drone will passively report hostile ship movement. Multiple ';
							echo 'drones will actively attack hostile ships.';

							echo '<br /><br />';
							
							if ($own_count > 0) {
								if ($spacegame['ship']['holds'] - $spacegame['cargo_volume'] < $own_count) {
									echo 'You do not have enough cargo space to pick up all of the drones.';
								}
								else {
									echo 'Click <a href="handler.php?task=ship&amp;subtask=pickup&amp;good=34&amp;return=ship">here</a> ';
									echo 'to retrieve '. $own_count .' drone(s).';
								}

								echo '<br /><br />';
							}

							if ($own_count >= MAX_ORDNANCE_PER_PLAYER || $total_count >= MAX_ORDNANCE_PER_SECTOR) {
								$deploy_amount = 0;

								echo 'This sector is at its limit for drones.';
							}
							elseif (isset($spacegame['system']) && $spacegame['system']['protected']) {
								$deploy_amount = 0;

								echo 'You cannot deploy drones in a protected sector.';
							}
							else {
								$deploy_amount = min(MAX_ORDNANCE_PER_PLAYER - $own_count, $tech['amount']);
								$deploy_amount = min(MAX_ORDNANCE_PER_SECTOR - $total_count, $deploy_amount);

								$selectable_amount = true;

								echo 'You can deploy up to ' . $deploy_amount . ' drones in this sector.';
							}

							break;

						case 'mines':

							$x = $spacegame['player']['x'];
							$y = $spacegame['player']['y'];
							$own_count = 0;
							$total_count = 0;

							$rs = $db->get_db()->query("select owner, amount from ordnance where x = {$x} and y = {$y} and good = '33'");

							$rs->data_seek(0);
							while ($row = $rs->fetch_assoc()) {
								$total_count += $row['amount'];

								if ($spacegame['player']['record_id'] == $row['owner']) {
									$own_count += $row['amount'];
								}
							}

							echo 'Players have a chance of hitting mines when leaving sectors. ';

							if ($own_count > 0) {
								echo 'Mines cannot be retrieved once deployed.';								
							}

							echo '<br /><br />';

							if ($own_count >= MAX_ORDNANCE_PER_PLAYER || $total_count >= MAX_ORDNANCE_PER_SECTOR) {
								$deploy_amount = 0;

								echo 'This sector is at its limit for mines.';
							}
							elseif (isset($spacegame['system']) && $spacegame['system']['protected']) {
								$deploy_amount = 0;

								echo 'You cannot deploy mines in a protected sector.';
							}
							else {
								$deploy_amount = min(MAX_ORDNANCE_PER_PLAYER - $own_count, $tech['amount']);
								$deploy_amount = min(MAX_ORDNANCE_PER_SECTOR - $total_count, $deploy_amount);

								$selectable_amount = true;

								echo 'You can deploy up to ' . $deploy_amount . ' mines in this sector.';
							}

							break;

						case 'base_package':

							echo 'A base provides a place for players to remain safe from ';
							echo 'attack, as long as the defenses hold out...';

							$deploy_amount = 1;
							$selectable_caption = true;
							break;


						default:
							echo 'Not sure what this does. Be careful with it...';
							break;
					}

					$counter++;
				?>
				<div class="docs_text">
					<form action="handler.php" method="post">
						<script type="text/javascript">drawButton('deploy<?php echo $counter; ?>', 'deploy', 'validate_deploy()');</script>
						<input type="hidden" name="task" value="ship" />
						<input type="hidden" name="subtask" value="deploy" />
						<input type="hidden" name="cargo_id" value="<?php echo $record_id; ?>" />
						<input type="hidden" name="return" value="ship" />

						<?php if ($selectable_amount) { ?>
							&nbsp;&nbsp;<input type="text" name="amount" value="<?php echo $deploy_amount; ?>" maxlength="5" size="4" />
						<?php } else { ?>
							<input type="hidden" name="amount" value="<?php echo $deploy_amount; ?>" />
						<?php } ?>

						<?php if ($selectable_caption) { ?>
							<br />
							<br />
							<label for="base_caption">Base Name:</label>
							<input type="text" id="base_caption" name="caption" maxlength="24" size="30" value="<?php echo DEFAULT_BASE_CAPTION; ?>" /><br />
						<?php } ?>
					</form>
				</div>
				<hr noshade="noshade" size="1" />
			</div>
		<?php } ?>
		<div class="docs_text">
			No further technology to deploy.
		</div>
	<?php } else { ?>
		<div class="docs_text">
			You are not carrying deployable technology.
		</div>
	<?php } ?>
	<hr />
	<div class="header2">Ship Name</div>
	<div class="docs_text">
		Your ship name can contain 2 to 12 characters using letters, numbers, and underscore only,
		unless you have an active <a href="docs.php?page=gold" target="_blank">Gold Membership</a>.
	</div>
	<div class="docs_text">
		<form action="handler.php" method="post">
			<label for="ship_name"><small>Ship Name</small></label><br />
			<input class="ship_form_input" type="text" id="ship_name" name="ship_name" maxlength="12" value="<?php echo $spacegame['player']['ship_name']; ?>" /><br />
			<br />
			<?php if (false) { ?>
			<label for="ship_style"><small>Ship Style</small></label><br />
			<input class="ship_form_input" type="text" id="ship_style" name="ship_style" maxlength="80" size="40" /><br />
			
			<br />
			<?php } ?>
			<script type="text/javascript">drawButton('rename_ship', 'update', 'validate_rename_ship()');</script>
			<input type="hidden" name="task" value="ship" />
			<input type="hidden" name="subtask" value="rename" />
			<input type="hidden" name="return" value="ship" />
		</form>
	</div>
	<?php if (false) { ?>
		<div class="docs_text">
			Style strings are made up of decoration tags which are used to decorate the ship name.
			Each tag performs the operation for the specified number of characters. If the rules run
			out of characters the rest are ignored. If the rules run out before the end of the name
			the rest of the characters are dumped with no decoration.
		</div>
		<div class="docs_text">
			Here are some example tags:
		</div>
		<div class="docs_text">
			<ul>
				<li><strong>1#ccc;</strong> - Color one character grey (note only 3 char css hex colors are supported).</li>
				<li><strong>4#ccc#ccc;</strong> - Color 4 characters with a css3 gradient.</li>
				<li><strong>3x;</strong> - Just dump 3 characters with no decoration.</li>
				<li><strong>5b;</strong> - Bold 5 characters, can do 'i' and 'u' as well.</li>
				<li><strong>b;</strong> - Toggle bold without moving pointer, can do 'i' and 'u'</li>
			</ul>	
		</div>
		<div class="docs_text">
			The maximum length of a style string is 80 characters which is enough to style the 
			gaudiest ship name in the galaxy.
		</div>
	<?php } ?>
	<hr />
	<div class="header2">Jettison Cargo</div>
	<div class="docs_text">
		You can dump your cargo for a fee of <?php echo number_format(($spacegame['player']['level'] + 1) * CARGO_DUMP_COST * INFLATION_MULTIPLIER); ?>
		<img src="res/credits.png" width="20" height="20" /> credits and <?php echo CARGO_DUMP_TURNS; ?>
		turns. We do not advise planning trade routes with the jettisoning of cargo in mind.
	</div>
	<div class="docs_text">
		<form action="handler.php" method="post">
			<script type="text/javascript">drawButton('jettison_button', 'delete', 'validate_empty_cargo()');</script>
			<input type="hidden" name="task" value="ship" />
			<input type="hidden" name="subtask" value="empty_cargo" />
			<input type="hidden" name="return" value="ship" />
		</form>
	</div>
	<hr />
</div>
	
<?php
	include_once('tmpl/html_end.php');
?>