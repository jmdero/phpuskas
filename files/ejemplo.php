<?php

	$profile 												= $this->get_profile_sections ( array ( "id_status" => 1 ) );

	$category 												= $this->get_criteria_labels ( array ( "value" => "system_experience_category" ), 1 );

	$subcategory 											= $this->get_criteria_labels ( array ( "value" => "system_experience_category" ), 2 );

	$skills 												= $this->get_criteria_labels ( array ( "value" => "system_skills" ) );

	$languages 												= $this->get_languages ( array ( "relationship" => "desired", "person_section_person" => 1, "order" => "ASC", "by" => "name" ) );

	$competences 											= $this->get_competence_sections ( array ( "id_status" => 1, "order" => "ASC", "by" => "title" ) );

	$licenses 												= $this->get_criteria_labels ( array ( "value" => "system_licenses" ) );

	$owned_vehicles 										= $this->get_criteria_labels ( array ( "value" => "system_owned_vehicles" ) );

	$working_day_type 										= $this->get_criteria_labels ( array ( "value" => "system_working_day_type" ) );

	$can_apply 												= false;

	$application_submitted 									= false;

	if ( $this->obj->candidate_section->num_rows > 0 )
	{
		$matching_stars 									= 0;

		if ( $this->get ( "matching_filter" ) > 0 )
		{
			$matching_stars 								= round ( 1 + ( $this->get ( "matching_points" ) / 100 * 4 ), 2 );
		}

		$application_section 								= $this->obj->candidate_section->get_application_sections ( array ( "id_status" => 1, "id_job_section" => $this->get () ) );

		if ( $application_section->num_rows > 0 )
		{
			$application_submitted 							= true;

			$state_class 									= "alert-info";

			if ( in_array ( $application_section->get ( "state_code" ), array ( "system_application_section_state_accepted", "system_application_section_state_validated" ) ) )
			{
				$state_class 								= "alert-success";
			}
			else if ( $application_section->get ( "state_code" ) == "system_application_section_state_rejected" )
			{
				$state_class 								= "alert-danger";
			}

			$submitted_applications 						= $this->get_num_application_sections ( array ( "id_status" => 1 ) );
		}
		else
		{
			$can_apply 										= true;
		}
	}
?>

<script>

	$( document ).ready ( function ()
	{
		$( ".btn-accept-invitation" ).click ( function ()
		{
			$( ".invitation-buttons-wrapper,.btn.application-unseen" ).slideUp ( "fast" );

			$( "form.submit-application" ).slideDown ( "fast" );
		});

		$( ".btn-cancel-submit-application" ).click ( function ()
		{
			$( "form.submit-application" ).slideUp ( "fast" );

			$( ".invitation-buttons-wrapper,.btn.application-unseen" ).slideDown ( "fast" );
		});

		$( ".btn-cancel-invitation" ).click ( function ()
		{
			var x 											= confirm ( "<?php _e ( "Are you sure you want to discard this invitation?" ); ?>" );

			if ( x )
			{
				$( "form.submit-application" ).append ( '<input type="hidden" name="discard_invitation" value="1">' );

				$( "form.submit-application" ).submit ();
			}
		});

		$( "form.submit-application" ).submit ( function ()
		{
			web_lock_controls ();
		});
	});

</script>

<table class="main-table">
	<tbody>
		<tr>
			<td>

				<?php if ( $this->obj->_company_person_section->has_image () ) { ?>

					<div class="user-image" style="background-image: url('<?php echo $this->obj->_company_person_section->get_image_link ( 130 ); ?>');"></div>

				<?php } else { ?>

					<div class="user-image avatar"></div>

				<?php } ?>

			</td>
			<td>

				<h2 class="title"><?php echo $this->get ( "title" ); ?></h2>

				<?php if ( ( $profile->num_rows > 0 ) || ( $category->num_rows > 0 ) ) { ?>

					<div class="general-info-wrapper">

						<?php if ( $profile->num_rows > 0 ) { ?>

							<div class="general-info">

								<span class="lbl"><?php _e ( "Profile" ); ?>: </span><?php echo _implode ( ", ", $profile->get_field_values ( "title" ) ); ?>

							</div>

						<?php } ?>

						<?php if ( $category->num_rows > 0 ) { ?>

							<div class="general-info">

								<span class="lbl"><?php _e ( "Category" ); ?>: </span><?php echo _implode ( ", ", $category->get_field_values ( "title" ) ); ?>

								<?php if ( $subcategory->num_rows > 0 ) { ?> \ <?php echo _implode ( ", ", $subcategory->get_field_values ( "title" ) ); ?><?php } ?>

							</div>

						<?php } ?>

						<?php if ( $working_day_type->num_rows > 0 ) { ?>

							<div class="general-info">

								<span class="lbl"><?php _e ( "Working day type" ); ?>: </span><?php echo _implode ( ", ", $working_day_type->get_field_values ( "title" ) ); ?>

							</div>

						<?php } ?>

						<?php if ( ( $this->get ( "salary_min" ) != "" ) && ( $this->get ( "salary_max" ) != "" ) ) { ?>

							<div class="general-info">

								<span class="lbl"><?php _e ( "Salary range" ); ?>: </span><?php echo $this->get ( "salary_min" ); ?> - <?php echo $this->get ( "salary_max" ); ?> (<?php _e ( "gross annual" ); ?>)

							</div>

						<?php } ?>

					</div>

				<?php } ?>

				<?php if ( $can_apply ) { ?>

					<?php if ( $this->is_available () ) { ?>

						<form class="general-info-wrapper submit-application form-horizontal" method="POST" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" enctype="multipart/form-data">

							<div class="alert alert-block alert-info">

								<?php $data = $this->explain_matching_query ( $this->obj->candidate_section->get () ); ?>

								<?php include ( $this->path_template_common."_application_matching.tpl.php" ); ?>

								<h4><?php _e ( "Submit application for this offer" ); ?></h4>

								<div class="submit-application-explanation"><?php _e ( "submit-application-explanation" ); ?></div>

								<div class="form-group cv-wrapper">

									<label class="col-sm-1 control-label"><?php _e ( "CV" ); ?>:</label>

									<div class="col-sm-9">
										<?php

											$files 						= $this->obj->candidate_section->get_media_sections ( array ( "id_status" => 1, "relationship" => "cv" ) );

											if ( $files->num_rows > 0 )
											{
												?>

													<select id="application_cv" name="application_cv" class="form-control chosen" required>
														<?php for ( $files->i = 0; $files->i < $files->num_rows; $files->i++ ) { ?>
															<option value="<?php echo $files->get (); ?>"><?php echo $files->get ( "name" ); ?></option>
														<?php } ?>
													</select>
												<?php
											}
											else
											{
												?>

													<input type="file" id="userfile" name="userfile" class="form-control" required>

												<?php
											}

										?>
									</div>

								</div>

								<input type="hidden" name="id_object" value="<?php echo $this->obj->candidate_section->get (); ?>">

								<button type="submit" class="btn btn-primary"><?php _e ( "Submit application" ); ?></button>

							</div>

						</form>

					<?php } else { ?>

						<div class="alert alert-danger general-info-wrapper" role="alert">
							<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<?php _e ( "This offer is no longer available." ); ?>
						</div>

					<?php } ?>

				<?php } else if ( $application_submitted ) { ?>

					<div class="general-info-wrapper application-submitted">

						<div class="alert alert-block <?php echo $state_class; ?>">

							<?php $data = $this->explain_matching_query ( $this->obj->candidate_section->get () ); ?>

							<?php include ( $this->path_template_common."_application_matching.tpl.php" ); ?>

							<div class="application-submitted-box-info">

								<?php if ( in_array ( $application_section->get ( "state_code" ), array ( "system_application_section_state_invited", "system_application_section_state_not_interested" ) ) ) { ?>

									<?php if ( in_array ( $application_section->get ( "state_code" ), array ( "system_application_section_state_invited" ) ) ) { ?>

										<h4><?php _e ( "Prospector invitation" ); ?></h4>

										<div class="submit-application-explanation"><?php _e ( "Prospector invitation description" ); ?></div>

									<?php } else { ?>

										<h4><?php _e ( "Invitation declined" ); ?></h4>

										<div class="submit-application-explanation"><?php _e ( "Invitation declined description" ); ?></div>

									<?php } ?>

									<?php if ( $this->is_available () ) { ?>

										<div class="invitation-buttons-wrapper">

											<a class="btn btn-success btn-accept-invitation"><?php _e ( "Accept invitation" ); ?></a>

											<?php if ( in_array ( $application_section->get ( "state_code" ), array ( "system_application_section_state_invited" ) ) ) { ?>

												<a class="btn btn-danger btn-cancel-invitation"><?php _e ( "I'm not interested" ); ?></a>

											<?php } ?>

										</div>

										<form class="general-info-wrapper submit-application form-horizontal hide-me" method="POST" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" enctype="multipart/form-data">

											<div class="form-group cv-wrapper">

												<label class="col-sm-1 control-label"><?php _e ( "CV" ); ?>:</label>

												<div class="col-sm-9">
													<?php

														$files 						= $this->obj->candidate_section->get_media_sections ( array ( "id_status" => 1, "relationship" => "cv" ) );

														if ( $files->num_rows > 0 )
														{
															?>

																<select id="application_cv" name="application_cv" class="form-control chosen" required>
																	<?php for ( $files->i = 0; $files->i < $files->num_rows; $files->i++ ) { ?>
																		<option value="<?php echo $files->get (); ?>"><?php echo $files->get ( "name" ); ?></option>
																	<?php } ?>
																</select>
															<?php
														}
														else
														{
															?>

																<input type="file" id="userfile" name="userfile" class="form-control" required>

															<?php
														}

													?>
												</div>

											</div>

											<input type="hidden" name="id_object" value="<?php echo $this->obj->candidate_section->get (); ?>">

											<button type="submit" class="btn btn-primary"><?php _e ( "Submit application" ); ?></button>

											<button type="button" class="btn btn-default btn-cancel-submit-application"><?php _e ( "Cancel" ); ?></a>

										</form>

									<?php } else { ?>

										<p class="text-danger"><?php _e ( "This offer is no longer available." ); ?></p>

									<?php } ?>

								<?php } else { ?>

									<h4><?php _e ( "Application submitted" ); ?></h4>

									<div class="application-submitted-explanation"><?php _e ( "application-submitted-explanation" ); ?></div>

									<ul>

										<li><?php _e ( "Presentation date" ); ?>: <?php echo _redate ( $application_section->get ( "insert_date" ), false, true ); ?></li>

										<li><?php _e ( "Application state" ); ?>: <strong><?php echo $application_section->get ( "state_title" ); ?></strong></li>

										<?php if ( $application_section->get ( "state_code" ) != "system_application_section_state_rejected" ) { ?>

											<li><?php _e ( "Submitted applications" ); ?>: <?php echo $submitted_applications; ?></li>

										<?php } ?>

									</ul>

								<?php } ?>

							</div>

							<?php

								if ( ( $application_section->get ( "id_message_section" ) > 0 ) && ( $application_section->get ( "state_code" ) != "system_application_section_state_not_interested" ) )
								{
									$forum 						= $application_section->get_message_sections ();

									$chat_route 				= $forum->get_owner_route ();

									$chat_read_only 			= "";

									if ( $application_section->get ( "state_code" ) == "system_application_section_state_rejected" )
									{
										$chat_read_only 		= "&read_only=1";
									}

									$chat_link 					= ' data-onclick-popup-admin="&module=chat&action=chat&route='.$chat_route.$chat_read_only.'"';

									?>

									<a class="application-unseen btn btn-default" data-unseen_id_message_section="<?php echo $application_section->get ( "id_message_section" ); ?>" <?php echo $chat_link; ?>>

										<span class="txt"><?php _e ( "Chat" ); ?></span>

										<span class="number">0</span>

										<span class="lbl"><?php _e ( "unseen messages" ); ?></span>

									</a>

									<?php

									$user_message_sections 		= $this->obj->user->get_user_message_sections ( array ( "message_sections" => array ( $application_section->get ( "id_message_section" ) ) ) );

									if ( ( $this->_get["id_candidate_section"] == "chat" ) && ( $user_message_sections->num_rows > 0 ) && ( $user_message_sections->get ( "unseen" ) > 0 ) )
									{
										?>

										<script>

											$( document ).ready ( function ()
											{
												$( ".application-submitted .application-unseen" ).trigger ( "click" );
											});

										</script>

										<?php
									}
								}
							?>

						</div>

					</div>

				<?php } else if ( !( $this->obj->user->get () > 0 ) ) { ?>

					<div class="general-info-wrapper registration-required">

						<div class="alert alert-block alert-info">

							<h4><?php _e ( "Register and submit application for this offer" ); ?></h4>

							<div class="register-and-submit-application-explanation"><?php _e ( "register-and-submit-application-explanation" ); ?></div>

							<button type="button" class="btn btn-primary" data-onclick-popup="<?php echo $this->obj->user->get_simple_portlet_link ( "user_register" ); ?>&portlet_candidate=1"><?php _e ( "Register" ); ?></button>

						</div>

					</div>

				<?php } ?>

			</td>
		</tr>
		<tr>
			<td>

				<h4><?php _e ( "Organization" ); ?></h4>

				<?php if ( strlen ( $this->obj->_company->get ( "name" ) ) > 0 ) { ?>

					<div class="contact-item">
						<div class="ico organization"></div>
						<div class="text"><?php echo $this->obj->_company->get ( "name" ); ?></div>
					</div>

				<?php } ?>

				<?php $address = $this->get_address ( false, false ); ?>

				<?php if ( strlen ( $address ) > 0 ) { ?>

					<div class="contact-item">
						<div class="ico address"></div>
						<div class="text"><?php echo $address; ?></div>
					</div>

				<?php } ?>

				<?php if ( $skills->num_rows > 0 ) { ?>

					<h4><?php _e ( "Skills" ); ?></h4>

					<?php $skills->render ( "web_view_with_degree" ); ?>

				<?php } ?>

				<?php if ( $languages->num_rows > 0 ) { ?>

					<h4><?php _e ( "Languages" ); ?></h4>

					<?php $languages->render ( "web_view_with_level" ); ?>

				<?php } ?>

				<?php if ( $competences->num_rows > 0 ) { ?>

					<h4><?php _e ( "Competences" ); ?></h4>

					<?php $competences->render ( "web_view_with_degree" ); ?>

				<?php } ?>

			</td>
			<td>

				<?php if ( strlen ( $this->get ( "description" ) ) > 0 ) { ?>

					<h4><?php _e ( "Description" ); ?></h4>

					<div class="general-wrapper"><?php echo $this->get ( "description" ); ?></div>

				<?php } ?>

				<?php if ( strlen ( $this->get ( "tasks" ) ) > 0 ) { ?>

					<h4><?php _e ( "Tasks" ); ?></h4>

					<div class="general-wrapper"><?php echo $this->get ( "tasks" ); ?></div>

				<?php } ?>

				<?php if ( strlen ( $this->get ( "minimum_requirements" ) ) > 0 ) { ?>

					<h4><?php _e ( "Minimum requirements" ); ?></h4>

					<div class="general-wrapper"><?php echo $this->get ( "minimum_requirements" ); ?></div>

				<?php } ?>

				<?php if ( strlen ( $this->get ( "desired_requirements" ) ) > 0 ) { ?>

					<h4><?php _e ( "Desired requirements" ); ?></h4>

					<div class="general-wrapper"><?php echo $this->get ( "desired_requirements" ); ?></div>

				<?php } ?>

				<?php if ( ( $licenses->num_rows > 0 ) || ( $owned_vehicles->num_rows > 0 ) ) { ?>

					<h4><?php _e ( "Other requirements" ); ?></h4>

					<div class="other-data-wrapper">

						<ul class="other-data">

							<?php if ( $licenses->num_rows > 0 ) { ?>

								<li>

									<span class="lbl"><?php _e ( "Licenses" ); ?>: </span><?php echo _implode ( ", ", $licenses->get_field_values ( "title" ) ); ?>

								</li>

							<?php } ?>

							<?php if ( $owned_vehicles->num_rows > 0 ) { ?>

								<li>

									<span class="lbl"><?php _e ( "Owned vehicles" ); ?>: </span><?php echo _implode ( ", ", $owned_vehicles->get_field_values ( "title" ) ); ?>

								</li>

							<?php } ?>

						</ul>

					</div>

				<?php } ?>

			</td>
		</tr>
	</tbody>
</table>
