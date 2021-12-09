<?php

add_action( 'rest_api_init', 'universityRegisterSearch' );

function universityRegisterSearch() {
	register_rest_route( 'university/v1', 'search', array(
		'methods'  => WP_REST_SERVER::READABLE,
		'callback' => 'universitySearchResults'
	) );
}

function universitySearchResults( $data ) {
	$mainQuery = new WP_Query( array(
		'post_type' => array( 'post', 'page', 'professor', 'program', 'campus', 'event' ),
		's'         => sanitize_text_field( $data['term'] )
	) );

	$results = array(
		'generalInfo' => array(),
		'professors'  => array(),
		'programs'    => array(),
		'events'      => array(),
		'campuses'    => array()
	);

	while ( $mainQuery->have_posts() ) {
		$mainQuery->the_post();

		if ( get_post_type() == 'post' or get_post_type() == 'page' ) {
			$results['generalInfo'][] = array(
				'title'      => get_the_title(),
				'permalink'  => get_the_permalink(),
				'postType'   => get_post_type(),
				'authorName' => get_the_author()
			);
		}

		if ( get_post_type() == 'professor' ) {
			$results['professors'][] = array(
				'title'     => get_the_title(),
				'permalink' => get_the_permalink(),
				'image'     => get_the_post_thumbnail_url( 0, 'professorLandscape' )
			);
		}

		if ( get_post_type() == 'program' ) {
			$relatedCampuses = get_field('related_campus');

			if ($relatedCampuses) {
				foreach ($relatedCampuses as $campus) {
					$results['campuses'][] = array(
						'title' => get_the_title($campus),
						'permalink' => get_the_permalink($campus),
					);
				}
			}

			$results['programs'][] = array(
				'title'     => get_the_title(),
				'permalink' => get_the_permalink(),
				'id'        => get_the_ID(),
			);
		}

		if ( get_post_type() == 'campus' ) {
			$results['campuses'][] = array(
				'title'     => get_the_title(),
				'permalink' => get_the_permalink()
			);
		}

		if ( get_post_type() == 'event' ) {
			$dsc = null;
			if ( has_excerpt() ) {
				$dsc = get_the_excerpt();
			} else {
				$dsc = wp_trim_words( get_the_content(), 18 );
			}
			$eventDate           = new DateTime( get_field( 'event_date' ) );
			$results['events'][] = array(
				'title'       => get_the_title(),
				'permalink'   => get_the_permalink(),
				'month'       => $eventDate->format( 'M' ),
				'day'         => $eventDate->format( 'd' ),
				'description' => $dsc,
			);
		}

	}

	if ( $results['programs'] ) {
		$programsMetaQuery = array( 'relation' => 'OR' );

		foreach ( $results['programs'] as $item ) {
			$programsMetaQuery[] = array(
				'key'     => 'related_programs',
				'value'   => '"' . $item['id'] . '"',
				'compare' => 'LIKE',
			);
		}


		$programRelationshipQuery = new WP_Query( array(
			'post_type'  => array('professor', 'event'),
			'meta_query' => $programsMetaQuery
		) );


		while ( $programRelationshipQuery->have_posts() ) {
			$programRelationshipQuery->the_post();

			if ( get_post_type() == 'professor' ) {
				$results['professors'][] = array(
					'title'     => get_the_title(),
					'permalink' => get_the_permalink(),
					'image'     => get_the_post_thumbnail_url( 0, 'professorLandscape' )
				);
			}

			if ( get_post_type() == 'event' ) {
				$dsc = null;
				if ( has_excerpt() ) {
					$dsc = get_the_excerpt();
				} else {
					$dsc = wp_trim_words( get_the_content(), 18 );
				}
				$eventDate           = new DateTime( get_field( 'event_date' ) );
				$results['events'][] = array(
					'title'       => get_the_title(),
					'permalink'   => get_the_permalink(),
					'month'       => $eventDate->format( 'M' ),
					'day'         => $eventDate->format( 'd' ),
					'description' => $dsc,
				);
			}

		}

		$results['events'] = array_values( array_unique( $results['events'], SORT_REGULAR ) );
		$results['professors'] = array_values( array_unique( $results['professors'], SORT_REGULAR ) );
	}

	return $results;

}