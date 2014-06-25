
<div class="events-list">

		<?php 

		setlocale(LC_TIME, 'fr_FR');

		foreach ($events as &$event) { 

			$connections = null;
			$featured	 = $event->featured;
			$post = null;

			if ( $event->connections_id > 0 ) 
			{
				$connections =  kz_connections_by_id($event->connections_id);
				$connections_ts = strtotime($connections['ts']); 
				$timestamp = mktime(date("H", $connections_ts), date("i", $connections_ts), date("s", $connections_ts), date("n", $connections_ts), date("j", $connections_ts), date("Y", $connections_ts));

				$event->connections_timestamp = $timestamp;


				if ($event->extra_venue < 1)
				{
					$event->venue 		= stripslashes($connections['organization']);
					$event->longitude 	= $connections['addresses']['longitude']; 
					$event->latitude 	= $connections['addresses']['latitude']; 
					$event->address 	= stripslashes(kz_format_address($connections['addresses']));
				}
				$post 				= kz_post_by_connections_id($event->connections_id) ; //on n'en prend qu'un
				$event->post		= $post;

				if ($event->image=='')
				{
					$options 		= $connections['options'];
					$logo 			= $options['logo'];
					$event->image 	= get_connections_image_base().$logo['name'];
				}
			}
			else
			{
				$event->connections_id = 0;
				$connections_ts = strtotime("2013-01-01 00:00:00");
				$event->connections_timestamp = mktime(date("H", $connections_ts), date("i", $connections_ts), date("s", $connections_ts), date("n", $connections_ts), date("j", $connections_ts), date("Y", $connections_ts));///$connections_ts;
			}

			$start_date 	= new DateTime($event->start_date);
			$end_date 		= new DateTime($event->end_date);
			$modification_date = $event->modification_date;

			//reprise de l'historique : certains enregistrements avaient une modification_date à 0000-00-00 00:00:00
			//ce qui cause un pb de calcul du timestamp
			//cela ne devrait plus arriver pour les enregistrements crées après l'ajout de ce champ
			if ($event->modification_date =="0000-00-00 00:00:00" )
				$event->modification_date = "2013-01-01 00:00:00";
			
			$modification_date = strtotime($event->modification_date);
			$event->timestamp	= mktime(date("H", $modification_date), date("i", $modification_date), date("s", $modification_date), date("n", $modification_date), date("j", $modification_date), date("Y", $modification_date));

		?>

			<article 	class="selectableEvent shadow-light events entry clearfix <?php if (is_event_today($event) && !$day_after) {echo 'today';} ?> <?php if ($agenda) {echo 'agenda';} ?> <?php if ($day_after) {echo 'day-after';} ?>" 
						data-event="<?php echo $event->id; ?>" 
						data-connections="<?php echo $event->connections_id; ?>"
						data-timestamp="<?php echo $event->timestamp; ?>"
						data-connectionstimestamp="<?php echo $event->connections_timestamp; ?>"
						itemscope 
						itemtype="http://schema.org/Event">

				<meta itemprop="startDate" 	content="<?php echo $start_date->format('Y-m-d'); ?>">
				<meta itemprop="endDate" 	content="<?php echo $end_date->format('Y-m-d'); ?>">
				<meta itemprop="url" 		content="<?php echo site_url().'/agenda'; ?>">
				<meta itemprop="name" 		content="<?php echo stripslashes($event->title); ?>">
				<meta itemprop="image" 		content="<?php echo $event->image; ?>">
				<?php if ($event->address!='') { ?>
					<meta itemprop="location" content="<?php echo stripslashes($event->address); ?>">
				<?php } ?>

				<!--div class="date"-->
					<?php if (is_event_today($event) && !$day_after) {?>
						<span class="post-meta today">Aujourd&apos;<span>hui</span></span>
					<?php } else { ?>
						<span class="post-meta <?php if ($featured) {echo 'featured-event';} ?>"><?php echo strftime("%a", strtotime( $start_date->format('m/d/Y') )); ?><span><?php echo strftime("%d", strtotime( $start_date->format('m/d/Y') ) ); ?></span></span> <!-- //->format( 'd' )-->
					<?php } ?>
				<!--/div-->

				<div class="entry-thumbnail">
					<img src="<?php echo $event->image; ?>" alt="<?php echo stripslashes($event->title); ?>" title="<?php echo stripslashes($event->title); ?>" />
				</div><!-- .entry-thumbnail -->

				<header class="entry-header">
					<h2 class="entry-title">
						<?php echo stripslashes($event->title); ?>
					</h2>
					<span>
						<span class="booticon-time"></span><?php echo ($end_date!=null && $end_date!='' && $end_date!=$start_date) ? 'Du ' : 'Le '; ?>
							<?php echo $start_date->format('d/m'); ?>
						<?php echo ($end_date!=null && $end_date!='' && $end_date!=$start_date) ? (' au '.$end_date->format('d/m') ) : ''; ?>
					</span>
					<?php if ($event->venue!='') { ?>
						<span><span class="booticon-map-marker"></span><?php echo stripslashes($event->venue); ?></span>
					<?php } ?>
				</header><!-- .entry-header -->

			</article>

		<?php } ?>

</div> <!-- /events-list -->