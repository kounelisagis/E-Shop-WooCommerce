<?php
class WoofiltersWpf extends ModuleWpf {
	public $mainWCQuery = '';
	public $mainWCQueryFiltered = '';
	public $shortcodeWCQuery = array();
	public $shortcodeWCQueryFiltered = array();
	public $shortcodeFilterKey = 'wpf-filter-';
	public $currentFilterId = null;
	public $currentFilterWidget = true;
	public $renderModes = array();
	public $preselects = array();
	public $preFilters = array();
	public $displayMode = null;
	private $wcAttributes = null;
	public static $loadShortcode = array();
	public static $currentElementorClass = '';
	public $clauses = array();

	public function init() {
		DispatcherWpf::addFilter('mainAdminTabs', array($this, 'addAdminTab'));
		add_shortcode(WPF_SHORTCODE, array($this, 'render'));
		add_shortcode(WPF_SHORTCODE_PRODUCTS, array($this, 'renderProductsList'));
		add_shortcode(WPF_SHORTCODE_SELECTED_FILTERS, array($this, 'renderSelectedFilters'));

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'showAdminErrors' ) );
		} elseif ( '1' !== ReqWpf::getVar( 'wpf_skip' ) ) {
			if ( ! class_exists( 'Popup_Maker' ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'addScriptsLisener' ), 999 );
			}
			add_filter( 'yith_wapo_disable_jqueryui', function ( $d ) {
				return true;
			}, 999 );
		}

		FrameWpf::_()->addScript('jquery-ui-autocomplete', '', array('jquery'), false, true);

		add_action('woocommerce_product_query', array($this, 'loadProductsFilter'));
		add_action('woocommerce_shortcode_products_query', array($this, 'loadShortcodeProductsFilter'), 999, 3);
		//add_filter('woocommerce_product_query_tax_query', array($this, 'customProductQueryTaxQuery'), 10, 1);

		add_action('woocommerce_shortcode_before_products_loop', array($this, 'addWoocommerceShortcodeQuerySettings'), 10, 1);

		trait_exists('\Essential_Addons_Elementor\Template\Content\Product_Grid') && add_action('pre_get_posts', array($this, 'loadProductsFilterForProductGrid'), 999);

		add_filter('loop_shop_per_page', array($this, 'newLoopShopPerPage'), 20 );

		class_exists( 'WC_pif' ) && add_filter( 'post_class', array( $this, 'WC_pif_product_has_gallery' ) );
		add_filter('yith_woocompare_actions_to_check_frontend', array($this, 'addAjaxFilterForYithWoocompare'), 20 );

		// removing action for theme Themify Ultra
		add_action( 'wp_loaded', function () {
			remove_action('pre_get_posts', 'Tbp_Public::set_archive_per_page');
		} );

		add_filter('woocommerce_shortcode_products_query_results', array($this, 'queryResults'));
		add_action('elementor/widget/before_render_content', array($this, 'getElementorClass'));
		add_action('woocommerce_is_filtered', array($this, 'isFiltered'));
	}

	public function isFiltered( $filtered ) {
		if ( ! $filtered ) {
			$filtered = count(ReqWpf::get( 'get' )) > 0;
		}

		return $filtered;
	}

	public function newLoopShopPerPage( $count ) {
		$options = FrameWpf::_()->getModule('options')->getModel('options')->getAll();
		if ( isset($options['count_product_shop']) && isset($options['count_product_shop']['value']) && !empty($options['count_product_shop']['value']) ) {
			$count  = $options['count_product_shop']['value'];
		}
		return $count ;
	}

	public function addWooOptions( $args ) {
		if (get_option('woocommerce_hide_out_of_stock_items') == 'yes') {
			$args['meta_query'][] = array(
				array(
					'key'     => '_stock_status',
					'value'   => 'outofstock',
					'compare' => '!='
				)
			);
		}

		$options = FrameWpf::_()->getModule( 'options' )->getModel( 'options' )->getAll();
		if ( isset($options['hide_without_price']) && '1' === $options['hide_without_price']['value'] ) {
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => '_price',
					'value'   => '',
					'type'    => 'numeric',
					'compare' => '!='
				),
				array(
					'key'     => '_price',
					'value'   => 0,
					'type'    => 'numeric',
					'compare' => '!='
				)
			);
		}

		return $args;
	}
	public function addScriptsLisener() {
		$js = 'var v = jQuery.fn.jquery;
			if (v && parseInt(v) >= 3 && window.self === window.top) {
				var readyList=[];
				window.originalReadyMethod = jQuery.fn.ready;
				jQuery.fn.ready = function(){
					if(arguments.length && arguments.length > 0 && typeof arguments[0] === "function") {
						readyList.push({"c": this, "a": arguments});
					}
					return window.originalReadyMethod.apply( this, arguments );
				};
				window.wpfReadyList = readyList;
			}';
		wp_add_inline_script('jquery', $js, 'after');
	}

	public function setCurrentFilter( $id, $isWidget ) {
		$this->currentFilterId = $id;
		$this->currentFilterWidget = $isWidget;
	}

	public function getPreselectedValue( $val = '' ) {
		if (empty($val)) {
			return $this->preselects;
		}
		return isset($this->preselects[$val]) ? $this->preselects[$val] : null;
	}
	public function addPreselectedParams() {
		if (!is_admin()) {
			if (is_null($this->currentFilterId)) {
				global $wp_registered_widgets;
				$filterWidget = 'wpfwoofilterswidget';

				$widgetOpions = get_option('widget_' . $filterWidget);
				$sidebarsWidgets = wp_get_sidebars_widgets();
				$preselects = array();
				$filters = array();
				if ( is_array($sidebarsWidgets) && !empty($widgetOpions) ) {
					foreach ($sidebarsWidgets as $sidebar => $widgets) {
						if ( ( 'wp_inactive_widgets' === $sidebar || 'orphaned_widgets' === substr($sidebar, 0, 16) ) ) {
							continue;
						}
						if (is_array($widgets)) {
							foreach ($widgets as $widget) {
								$ids = explode('-', $widget);

								// trying to find the filter shortcode in the text widget
								$opts = $wp_registered_widgets[ $widget ];
								$id_base = is_array( $opts['callback'] ) ? $opts['callback'][0]->id_base : $opts['callback'];

								if ( ! $id_base ) {
									continue;
								}

								$instance = get_option( 'widget_' . $id_base );

								if ( ! $instance || ! is_array( $instance ) ) {
									continue;
								}

								foreach ( $instance as $item ) {
									if ( isset( $item['text'] ) ) {
										preg_match( '/\[wpf-filters id=(\d+)\]/', $item['text'], $matches );
										if ( isset( $matches[1] ) ) {
											$filterId = $matches[1];
											$preselects = array_merge($preselects, $this->getPreselectedParamsForFilter($filterId));
											$filters[$filterId] = 1;
										}
									}
								}

								// if the filter is added using the Legacy Widget
								if ( count($ids) == 2 && $ids[0] == $filterWidget ) {
									if ( isset($widgetOpions[$ids[1]]) && isset($widgetOpions[$ids[1]]['id']) ) {
										$filterId = $widgetOpions[$ids[1]]['id'];

										if (!isset($filters[$filterId])) {
											$preselects = array_merge($preselects, $this->getPreselectedParamsForFilter($filterId));
											$filters[$filterId] = 1;
										}
									}
								}

							}
						}
					}
				}
			} else {
				$preselects = $this->getPreselectedParamsForFilter($this->currentFilterId);
			}

			$this->preselects = array();
			foreach ($preselects as $value) {
				if (!empty($value)) {
					$paar = explode('=', $value);
					if (count($paar) == 2) {
						$name = $paar[0];
						$var = $paar[1];
						if ( 'min_price' == $name || 'max_price' == $name ) {
							$var = $this->getCurrencyPrice($var);
						}

						$this->preselects[$name] = $var;
					}
				}
			}
		}
	}
	public function getPreselectedParamsForFilter( $filterId ) {
		if (!isset($this->preFilters[$filterId])) {
			$preselects = array();
			$filter = $this->getModel('woofilters')->getById($filterId);
			if ($filter) {
				$settings = unserialize($filter['setting_data']);
				$preselect = !empty($settings['settings']['filters']['preselect']) ? $settings['settings']['filters']['preselect'] : '';
				if (!empty($preselect)) {
					$mode = $this->getRenderMode($filterId, $settings);
					if ($mode > 0) {
						$preselects = explode(';', $preselect);
					}
				}
				if ( defined('WPF_FREE_REQUIRES') && version_compare( '1.4.9', WPF_FREE_REQUIRES, '==' ) ) {
					$preselects = DispatcherWpf::applyFilters( 'addDefaultFilterData', $preselects, $filterId, $settings );
				} else {
					DispatcherWpf::doAction( 'addDefaultFilterData', $filterId, $settings );
				}
			}
			$this->preFilters[$filterId] = $preselects;
		}
		return $this->preFilters[$filterId];
	}

	public function searchValueQuery( $arrQuery, $key, $value, $delete = false ) {
		if (empty($arrQuery)) {
			return false;
		}
		foreach ($arrQuery as $i => $q) {
			if (is_array($q) && isset($q[$key]) && $value == $q[$key]) {
				if ($delete) {
					unset($arrQuery[$i]);
				} else {
					return $i;
				}
			}
		}
		return $arrQuery;
	}

	public function addCustomFieldsQuery( $data, $mode ) {
		$fields = array();
		if (count($data) == 0) {
			return $fields;
		}

		if (!empty($data['pr_onsale'])) {
			$fields['post__in'] = array_merge(array(0), wc_get_product_ids_on_sale());
		}
		if (!empty($data['pr_author'])) {
			$slugs = explode('|', $data['pr_author']);

			$userIds = array();
			foreach ( $slugs as $userSlug ) {
				$userObj = get_user_by( 'slug', $userSlug );
				if ( isset( $userObj->ID ) ) {
					$userIds[] = $userObj->ID;
				}
			}

			if ( ! empty( $userIds ) ) {
				$fields['author__in'] = $userIds;
			}
		}
		if (!empty($data['vendors'])) {
			$userObj = get_user_by('slug', ReqWpf::getVar('vendors'));
			if (isset($userObj->ID)) {
				$fields['author'] = $userObj->ID;
			}
		}
		if (!empty($data['wpf_count'])) {
			$fields['posts_per_page'] = $data['wpf_count'];
		}

		$fields = DispatcherWpf::applyFilters('addCustomFieldsQueryPro', $fields, $data, $mode);
		return $fields;
	}

	public function addCustomMetaQuery( $metaQuery, $data, $mode ) {
		if (!is_array($metaQuery)) {
			$metaQuery = array();
		}
		
		if (count($data) == 0) {
			return $metaQuery;
		}
		$price = $this->preparePriceFilter(empty($data['min_price']) ? null : $data['min_price'], empty($data['max_price']) ? null : $data['max_price']);
		if (false != $price) {
			$metaQuery = array_merge($metaQuery, $price);
			remove_filter('posts_clauses', array(WC()->query, 'price_filter_post_clauses' ), 10, 2);
		}
		if (!empty($data['pr_stock'])) {
			$slugs = explode('|', $data['pr_stock']);
			if ($slugs) {
				$metaQuery = $this->searchValueQuery($metaQuery, 'key', '_stock_status', true);
				$metaQuery[] = array(
					'key' => '_stock_status',
					'value' => $slugs,
					'compare' => 'IN'
				);
			}			
		}
		if (!empty($data['pr_rating'])) {
			$ratingRange = $data['pr_rating'];
			$range = strpos($ratingRange, '-') !== false ? explode('-', $ratingRange) : array(intval($ratingRange));
			if (isset($range[1]) && intval($range[1]) !== 5) {
				$range[1] = $range[1] - 0.001;
			}
			if ($range[0] && isset($range[1]) && $range[1]) {
				$metaQuery = $this->searchValueQuery($metaQuery, 'key', '_wc_average_rating', true);
				$metaQuery[] = array(
					'key' => '_wc_average_rating',
					'value' => array($range[0], $range[1]),
					'type' => 'DECIMAL',
					'compare' => 'BETWEEN'
				);
			} elseif ($range[0]) {
				$metaQuery = $this->searchValueQuery($metaQuery, 'key', '_wc_average_rating', true);
				$metaQuery[] = array(
					'key' => '_wc_average_rating',
					'value' => $range[0],
					'type' => 'DECIMAL',
					'compare' => '='
				);
			}
		}
		$metaQuery = DispatcherWpf::applyFilters('addCustomMetaQueryPro', $metaQuery, $data, $mode);
		return $metaQuery;
	}

	public function addCustomTaxQuery( $taxQuery, $data, $mode ) {

		if (!is_array($taxQuery)) {
			$taxQuery = array();
		}
		
		$isSlugs = ( 'url' == $mode );
		$isPreselect = ( 'preselect' == $mode );
		// custom tahonomy attr block
		if (!empty($taxQuery)) {
			foreach ($taxQuery as $i => $tax) {
				if (is_array($tax) && isset($tax['field']) && 'slug' == $tax['field']) {
					$name = str_replace('pa_', 'filter_', $tax['taxonomy']);
					if ($isPreselect && ReqWpf::getVar($name)) {
						unset($taxQuery[$i]);
						continue;
					}
					if (!empty($data[$name])) {
						$param = $data[$name];
						$slugs = explode('|', $param);
						if (count($slugs) > 1) {
							$taxQuery[$i]['terms'] = $slugs;
							$taxQuery[$i]['operator'] = 'IN';
						}
					}
				}
			}
		}

		if (count($data) == 0) {
			return $taxQuery;
		}
		
		foreach ($data as $key => $param) {
			if ( is_string( $param ) ) {
				$isNot = ( substr( $param, 0, 1 ) === '!' );
				if ( $isNot ) {
					$param = substr( $param, 1 );
				}
				if ( strpos( $key, 'filter_cat_list' ) !== false ) {
					if ( ! empty( $param ) ) {
						$idsAnd     = explode( ',', $param );
						$idsOr      = explode( '|', $param );
						$isAnd      = count( $idsAnd ) > count( $idsOr );
						$taxQuery[] = array(
							'taxonomy'         => 'product_cat',
							'field'            => ( substr( $key, - 1 ) == 's' ? 'slug' : 'term_id' ),
							'terms'            => $isAnd ? $idsAnd : $idsOr,
							'operator'         => $isNot ? 'NOT IN' : ( $isAnd ? 'AND' : 'IN' ),
							'include_children' => false,
						);
					}
				} else if ( strpos( $key, 'filter_cat_' ) !== false || ( 'filter_cat' == $key ) ) {
					if ( ! empty( $param ) ) {
						$idsAnd     = explode( ',', $param );
						$idsOr      = explode( '|', $param );
						$isAnd      = count( $idsAnd ) > count( $idsOr );
						$taxQuery[] = array(
							'taxonomy'         => 'product_cat',
							'field'            => ( substr( $key, - 1 ) == 's' ? 'slug' : 'term_id' ),
							'terms'            => $isAnd ? $idsAnd : $idsOr,
							'operator'         => $isNot ? 'NOT IN' : ( $isAnd ? 'AND' : 'IN' ),
							'include_children' => true,
						);
					}
				} else if ( strpos( $key, 'product_tag' ) === 0 ) {
					if ( ! empty( $param ) ) {
						$idsAnd     = explode( ',', $param );
						$idsOr      = explode( '|', $param );
						$isAnd      = count( $idsAnd ) > count( $idsOr );
						$taxQuery[] = array(
							'taxonomy'         => 'product_tag',
							'field'            => $isSlugs ? 'slug' : 'id',
							'terms'            => $isAnd ? $idsAnd : $idsOr,
							'operator'         => $isNot ? 'NOT IN' : ( $isAnd ? 'AND' : 'IN' ),
							'include_children' => true,
						);
					}
				} else if ( strpos( $key, 'product_brand' ) === 0 ) {
					if ( ! empty( $param ) ) {
						$idsOr      = explode( ',', $param );
						$idsAnd     = explode( '|', $param );
						$isAnd      = count( $idsAnd ) > count( $idsOr );
						$taxQuery[] = array(
							'taxonomy'         => 'product_brand',
							'field'            => $isSlugs ? 'slug' : 'id',
							'terms'            => $isAnd ? $idsAnd : $idsOr,
							'operator'         => $isNot ? 'NOT IN' : ( $isAnd ? 'AND' : 'IN' ),
							'include_children' => true,
						);
					}
				} else if ( strpos( $key, 'filter_pwb_list' ) !== false ) {
					if ( ! empty( $param ) ) {
						$idsAnd     = explode( ',', $param );
						$idsOr      = explode( '|', $param );
						$isAnd      = count( $idsAnd ) > count( $idsOr );
						$taxQuery[] = array(
							'taxonomy'         => 'pwb-brand',
							'field'            => 'term_id',
							'terms'            => $isAnd ? $idsAnd : $idsOr,
							'operator'         => $isNot ? 'NOT IN' : ( $isAnd ? 'AND' : 'IN' ),
							'include_children' => false,
						);
					}
				} elseif ( strpos( $key, 'filter_pwb' ) !== false ) {
					if ( ! empty( $param ) ) {
						$idsAnd     = explode( ',', $param );
						$idsOr      = explode( '|', $param );
						$isAnd      = count( $idsAnd ) > count( $idsOr );
						$taxQuery[] = array(
							'taxonomy'         => 'pwb-brand',
							'field'            => 'term_id',
							'terms'            => $isAnd ? $idsAnd : $idsOr,
							'operator'         => $isNot ? 'NOT IN' : ( $isAnd ? 'AND' : 'IN' ),
							'include_children' => true,
						);
					}
				} elseif ( strpos( $key, 'pr_filter' ) !== false ) {
					if ( ! empty( $param ) ) {
						$exeptionalLogic = 'not_in';
						$logic           = $this->getAttrFilterLogic();
						if ( ! empty( $logic['delimetr'][ $exeptionalLogic ] ) ) {
							$ids        = explode( $logic['delimetr'][ $exeptionalLogic ], $param );
							$taxonomy   = str_replace( 'pr_filter_', 'pa_', $key );
							$taxQuery[] = array(
								'taxonomy' => $taxonomy,
								'field'    => 'slug',
								'terms'    => $ids,
								'operator' => $logic['loop'][ $exeptionalLogic ],
							);
						}
					}
				} else if ( strpos( $key, 'filter_' ) === 0 ) {
					if ( ! empty( $param ) ) {
						$idsAnd    = explode( ',', $param );
						$idsOr     = explode( '|', $param );
						$isAnd     = count( $idsAnd ) > count( $idsOr );
						$attrIds   = $isAnd ? $idsAnd : $idsOr;
						$taxExists = false;
						if ( $isSlugs ) {
							$taxonomy  = str_replace( 'filter_', '', $key );
							$taxonomy  = preg_replace( '/_\d{1,}/', '', $taxonomy );
							$taxExists = taxonomy_exists( $taxonomy );
							if ( ! $taxExists ) {
								$taxonomy  = 'pa_' . $taxonomy;
								$taxExists = taxonomy_exists( $taxonomy );
							}
						} else {
							$taxonomy = '';
							foreach ( $attrIds as $attr ) {
								$term = get_term( $attr );
								if ( $term ) {
									$taxonomy  = $term->taxonomy;
									$taxExists = true;
									break;
								}
							}
						}
						if ( $taxExists ) {
							$taxQuery[] = array(
								'taxonomy' => $taxonomy,
								'field'    => $isSlugs ? 'slug' : 'id',
								'terms'    => $attrIds,
								'operator' => $isNot ? 'NOT IN' : ( $isAnd ? 'AND' : 'IN' )
							);
						}
					}
				}
			}
		}

		if (!empty($data['pr_featured'])) {
			$taxQuery = $this->searchValueQuery($taxQuery, 'taxonomy', 'product_visibility', true);
			$taxQuery[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'featured'
			);
		}
		$taxQuery = DispatcherWpf::applyFilters('addCustomTaxQueryPro', $taxQuery, $data, $mode);

		return $taxQuery;
	}

	public function loadProductsFilter( $q ) {
		$this->addPreselectedParams();

		if (ReqWpf::getVar('all_products_filtering')) {
			$exclude = array('paged', 'posts_per_page', 'post_type', 'wc_query', 'orderby', 'order', 'fields');
			foreach ($q->query as $queryVarKey => $queryVarValue) {
				if (!in_array($queryVarKey, $exclude)) {
					if (is_string($queryVarValue)) {
						$q->set($queryVarKey, '');
					}
					if (is_array($queryVarValue)) {
						$q->set($queryVarKey, array());
					}
				}
			}
		} else {
			$search = ReqWpf::getVar('s');
			if (!is_null($search) && !empty($search)) {
				$q->set('s', $search);
			}
		}

		$metaQuery = $q->get('meta_query');
		$taxQuery = $q->get('tax_query');

		// set preselects
		$mode = 'preselect';
		$preselects = $this->getPreselectedValue();
		$fields = $this->addCustomFieldsQuery($preselects, $mode);
		$metaQuery = $this->addCustomMetaQuery($metaQuery, $preselects, $mode);
		$taxQuery = $this->addCustomTaxQuery($taxQuery, $preselects, $mode);

		$q->set('meta_query', $metaQuery);
		$q->set('tax_query', $this->groupTaxQueryArgs($taxQuery));
		foreach ($fields as $key => $value) {
			$q->set($key, $value);
		}

		// added an additional check, since meta_query can be added by other plugins and, as a result, the request crashed
		if ( empty( $q->get( 'meta_query' ) ) || 'product_query' === $q->get( 'wc_query' ) ) {
			$q->set( 'post_type', 'product' );
		}
		$this->mainWCQuery = $q->query_vars;

		$this->fields    = array();
		$args = $this->getQueryVars( $this->mainWCQuery );

		if ( $this->mainWCQuery!==$args ) {

			$q->set( 'meta_query', $args['meta_query'] );
			$q->set( 'tax_query', $args['tax_query'] );
			foreach ( $this->fields as $key => $value ) {
				$q->set( $key, $value );
			}
		}

		if (ReqWpf::getVar('wpf_order')) {
			add_filter( 'posts_clauses', array($this, 'addClausesTitleOrder'));
		}
		if (FrameWpf::_()->proVersionCompare('1.4.8')) {
			$filterSettings = array();
			$params = array();
			if (ReqWpf::getVar('wpf_fbv')) {
				$filterSettings = array( 'filtering_by_variations' => 1 );
				$params = ReqWpf::get('get');
			}
			$args = array(
				'tax_query'  => $q->get('tax_query'),
				'meta_query' => $q->get('meta_query'),
				'post__in'   => $q->get('post__in'),
			);
			$args = $this->addBeforeFiltersFrontendArgs($args, $filterSettings, $params);
			$q->set('post__in', $args['post__in']);
			$q->set('tax_query', $args['tax_query']);
		}

		$q = DispatcherWpf::applyFilters('loadProductsFilterPro', $q);
		if ( $this->mainWCQuery !== $q->query_vars ) {
			$this->mainWCQueryFiltered = $q->query_vars;
		}
		// removes hooks that could potentially override filter settings
		remove_all_filters('wpv_action_apply_archive_query_settings');

		// allow show subcategories only if nothing is selected
		$params = ReqWpf::get( 'get' );
		if ( ! empty( $params ) ) {
			$unsetParam = array( 'wpf_count', 'wpf_fbv', 'wpf_dpv', 'wpf_skip', '_' );
			foreach ( $params as $param=>$value ) {
				if ( in_array( $param, $unsetParam, true ) ) {
					unset( $params[ $param ] );
				}
			}
			if ( ! empty( $params ) ) {
				remove_filter( 'woocommerce_product_loop_start', 'woocommerce_maybe_show_product_subcategories' );
			}
		}
	}


	public function getQueryVars( $args, $exludeParam = array() ) {
		// set url params
		$mode   = 'url';
		$params = ReqWpf::get( 'get' );

		if ( ! empty( $exludeParam ) && isset( $params[ $exludeParam ] ) ) {
			unset( $params[ $exludeParam ] );
		}

		if ( count( $params ) === 0 ) {
			$mode   = 'default';
			$params = DispatcherWpf::applyFilters( 'getDefaultFilterParams', $params );
		}

		if ( count( $params ) > 0 ) {
			$taxQuery     = $this->addCustomTaxQuery( $args['tax_query'], $params, $mode );
			$params       = array_merge( $this->preselects, $params );
			$this->fields = $this->addCustomFieldsQuery( $params, $mode );
			$metaQuery    = $this->addCustomMetaQuery( $args['meta_query'], $params, $mode );

			$args['meta_query'] = $metaQuery;
			$args['tax_query']  = $this->groupTaxQueryArgs( $taxQuery );
			foreach ( $this->fields as $key => $value ) {
				$args[ $key ] = $value;
			}
			if ( empty( $args['post_type'] ) ) {
				$args['post_type'] = 'product';
			}
		}

		return $args;
	}
	
	public function loadProductsFilterForProductGrid( $q ) {
		if ('product' == $q->get('post_type')) {
			global $paged;
			$this->loadProductsFilter($q);
			if ($paged && $paged > 1) {
				$q->set('paginate', true);
				$q->set('paged', $paged);
			}
			//$q->set('tax_query', $this->addCustomTaxQuery($this->mainWCQuery->get('tax_query') ));
			//$this->mainWCQuery = $q;
		}
	}

	public function loadShortcodeProductsFilter( $args, $attributes, $type ) {
		$hash      = md5( serialize( $args ) );
		$filterKey = ( empty( $attributes['class'] ) ) ? ( ( empty( self::$currentElementorClass ) ) ? '-' : self::$currentElementorClass ) : $attributes['class'];

		if ( ! key_exists( $hash, self::$loadShortcode ) || 'products' !== $type ) {
			$this->addPreselectedParams();

			$metaQuery = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
			$taxQuery  = isset( $args['tax_query'] ) ? $args['tax_query'] : array();

			// set preselects
			$mode       = 'preselect';
			$preselects = $this->getPreselectedValue();
			$fields     = $this->addCustomFieldsQuery( $preselects, $mode );
			$metaQuery  = $this->addCustomMetaQuery( $metaQuery, $preselects, $mode );
			$taxQuery   = $this->addCustomTaxQuery( $taxQuery, $preselects, $mode );

			$args['meta_query'] = $metaQuery;
			$args['tax_query']  = $this->groupTaxQueryArgs( $taxQuery );
			foreach ( $fields as $key => $value ) {
				$args[ $key ] = $value;
			}
			if ( empty( $args['post_type'] ) ) {
				$args['post_type'] = 'product';
			}

			$filterId = null;
			if ( '-' !== $filterKey ) {
				preg_match( '/.+?-(\d+)/', $filterKey, $matches );
				if ( isset( $matches[1] ) ) {
					$filterId = $matches[1];
				}
			}
			$isClassFilterId = ! is_null( $filterId );

			$this->shortcodeWCQuery[ $filterKey ] = $args;

			$params = ReqWpf::get( 'get' );
			if ( ! $isClassFilterId || ( isset( $params['wpf_id'] ) && $filterId === $params['wpf_id'] ) ) {
				$args = $this->getQueryVars( $args );
				if ( ReqWpf::getVar( 'orderby' ) ) {
					remove_all_filters( 'posts_orderby' );
					remove_all_filters( 'woocommerce_get_catalog_ordering_args' );
					$WC_Query = new WC_Query();
					$fields   = $WC_Query->get_catalog_ordering_args( ReqWpf::getVar( 'orderby' ) );
					if ( is_array( $fields ) ) {
						$args = array_merge( $args, $fields );
					}
				}

				if ( ReqWpf::getVar( 'wpf_order' ) ) {
					$args['order']   = $this->getWpfOrderParam( ReqWpf::getVar( 'wpf_order' ) );
					$args['orderby'] = 'title';
				}

				$filterSettings = array();
				if ( ReqWpf::getVar( 'wpf_fbv' ) ) {
					$filterSettings = array( 'filtering_by_variations' => 1 );
				}
				if ( FrameWpf::_()->proVersionCompare( '1.4.8' ) ) {
					$args = $this->addBeforeFiltersFrontendArgs( $args, $filterSettings, $params );
				} else {
					$args = DispatcherWpf::applyFilters( 'checkBeforeFiltersFrontendArgs', $args, $filterSettings, $params );
				}
				if ( $this->shortcodeWCQuery[ $filterKey ] !== $args ) {
					$this->shortcodeWCQueryFiltered[ $filterKey ] = $args;
				}
			}
			self::$loadShortcode[ $hash ] = $args;
		} else {
			$args = self::$loadShortcode[ $hash ];
		}

		return $args;

	}

	public function addBeforeFiltersFrontendArgs( $args, $filterSettings = array(), $urlQuery = array() ) {
		$vars = ( ! empty( $urlQuery ) ) ? $urlQuery : ReqWpf::get( 'get' );

		if ( ! empty( $args ) ) {
			global $wpdb;
			$args['post_type'] = array( 'product' );
			$settingsFilteringByVariations = ! empty( $filterSettings ) && isset( $filterSettings['filtering_by_variations'] ) ? $filterSettings['filtering_by_variations'] : false;
			if ( $settingsFilteringByVariations && ! isset( $args['variations'] ) ) {
				$args['post_type'][] = 'product_variation';
				if ( isset( $args['tax_query'] ) && ! empty( $args['tax_query'] ) ) {
					$allAttributes   = $this->getWcAttributeTaxonomies();
					$logicInnerOrAll = true;
					$clauses         = array();
					$select          = array();
					$countBlock      = 0;
					$logic           = 'AND';
					$postIds         = empty( $args['post__in'] ) ? '' : ' AND p.ID IN(' . implode( ',', $args['post__in'] ) . ')';
					$taxonomies = array();

					foreach ( $args['tax_query'] as $keyTax => &$tax_query ) {
						if ( ! is_array( $tax_query ) ) {
							continue;
						}

						if ( isset( $tax_query['relation'] ) ) {
							$logic = $tax_query['relation'];
						}

						if ( isset( $tax_query['taxonomy'] ) ) {
							$tax_query = array( $tax_query );
						}

						$countTerm = 0;
						$deleteTerm = 0;
						foreach ( $tax_query as $key => $tax_item ) {

							if ( ! is_array( $tax_item ) || empty( $tax_item['taxonomy'] ) ) {
								continue;
							}

							$countTerm ++;

							$taxonomy = $tax_item['taxonomy'];

							if ( in_array( $taxonomy, $allAttributes, true ) ) {
								$countBlock ++;

								$isSlug          = ( isset( $tax_item['field'] ) && 'slug' === $tax_item['field'] );
								$countItem       = 0;
								$termTtaxonomyId = array();
								$metaValue       = array();
								foreach ( $tax_item['terms'] as $keyTerm => $termId ) {
									$term = $isSlug ? get_term_by( 'slug', $termId, $taxonomy ) : get_term( $termId );
									if ( $term ) {
										$countItem ++;
										$termTtaxonomyId[] = $term->term_id;
										$metaValue[]       = "'{$term->slug}'";
										$taxonomies[]      = $taxonomy;
										unset( $tax_query[ $key ]['terms'][ $keyTerm ] );
									}
								}
								if ( ! empty( $termTtaxonomyId ) && ! empty( $metaValue ) ) {
									$clauses[ $key ]['join']           = " JOIN {$wpdb->term_relationships} tr{$key} ON p.ID = tr{$key}.object_id ";
									$clauses[ $key ]['where']          = " AND tr{$key}.term_taxonomy_id IN (" . implode( ', ', $termTtaxonomyId ) . ')';
									$clauses[ $key ]['joinVariation']  = " JOIN {$wpdb->postmeta} pm{$key} ON p.ID = pm{$key}.post_id AND pm{$key}.meta_key = 'attribute_{$taxonomy}'";
									$clauses[ $key ]['whereVariation'] = " AND pm{$key}.meta_value IN (" . implode( ', ', $metaValue ) . ')';
									if ( isset( $tax_item['operator'] ) && 'AND' === $tax_item['operator'] ) {
										$clauses[ $key ]['having'] = " HAVING COUNT(*) >= {$countItem}";
										$logicInnerOrAll           = false;
									} else {
										$clauses[ $key ]['having'] = '';
									}
								}

								if ( empty( $tax_query[ $key ]['terms'] ) ) {
									unset( $tax_query[ $key ] );
									$deleteTerm ++;
								}

							}
						}
						if ( $deleteTerm > 0 && $deleteTerm === $countTerm ) {
							unset( $args['tax_query'][ $keyTax ] );
						}
					}

					if ( ! empty( $vars ) ) {
						$metaCustom = preg_grep( '/f_meta_/', array_flip( $vars ) );
						$key        = count( $clauses );
						foreach ( $metaCustom as $ids => $metaKey ) {
							$key ++;
							$ids                               = preg_replace( array( '/[^0-9\|]/', '/\|/' ), array( '', ',' ), $ids );
							$wpdb->wpf_prepared_query          = "SELECT GROUP_CONCAT( meta_value ) FROM	{$wpdb->postmeta} WHERE meta_id IN ($ids)";
							$metaValue                         = $wpdb->get_var( $wpdb->wpf_prepared_query );
							$metaKey                           = str_replace( 'f_meta_', '', $metaKey );
							$clauses[ $key ]['join']           = " JOIN {$wpdb->postmeta} pm{$key} ON p.ID = pm{$key}.post_id AND pm{$key}.meta_key = '{$metaKey}'";
							$clauses[ $key ]['joinVariation']  = $clauses[ $key ]['join'];
							$clauses[ $key ]['where']          = " AND pm{$key}.meta_value IN ({$metaValue})";
							$clauses[ $key ]['whereVariation'] = $clauses[ $key ]['where'];
						}
					}

					if ( ! empty( $clauses ) ) {
						$joinAdd = " JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id AND pm.meta_key='_stock_status' AND pm.meta_value != 'outofstock'";
						$options = FrameWpf::_()->getModule( 'options' )->getModel( 'options' )->getAll();
						if ( isset($options['hide_without_price']) &&  '1' === $options['hide_without_price']['value'] ) {
							$joinAdd .= " JOIN {$wpdb->postmeta} pmp ON p.ID=pmp.post_id AND pmp.meta_key='_price' AND pmp.meta_value > 0";
						}
						$joinAttributes = " JOIN {$wpdb->postmeta} pma ON p.ID = pma.post_id AND pma.meta_key = '_product_attributes'";

						$variable = get_term_by('slug' , 'variable', 'product_type');

						if ( $logicInnerOrAll && 'AND' === $logic ) {
							$join             = '';
							$where            = '';
							$joinVariation    = '';
							$whereVariation   = '';
							$excludeVariation = '';
							foreach ( $clauses as $key => $value ) {
								$join             .= $value['join'];
								$where            .= $value['where'];
								$joinVariation    .= $value['joinVariation'];
								$whereVariation   .= $value['whereVariation'];
								$excludeVariation .= "{$value['joinVariation']}{$value['whereVariation']}";
							}


							$sql =
								"SELECT p.ID, pma.meta_value AS attributes, trv.term_taxonomy_id AS type_id
								FROM {$wpdb->posts} p 
								LEFT JOIN {$wpdb->term_relationships} trv ON p.ID = trv.object_id AND trv.term_taxonomy_id = {$variable->term_id}	
								{$join} 
								{$joinAdd}
								{$joinAttributes}
								WHERE p.post_type = 'product' {$postIds}
								AND p.ID NOT IN (SELECT DISTINCT p.post_parent 
								FROM {$wpdb->posts} p {$excludeVariation} 
								WHERE p.post_type = 'product_variation')     
								{$where}
                        	UNION 
							SELECT p.post_parent, '' as attributes, '' AS type_id 
								FROM {$wpdb->posts} p								 
								{$joinVariation}
								{$joinAdd}
								WHERE p.post_type = 'product_variation'
								{$whereVariation}";

							$wpdb->wpf_prepared_query = $sql;
						} else {
							foreach ( $clauses as $key => $value ) {
								$selectProduct   =
									"SELECT p.ID, pma.meta_value AS attributes, trv.term_taxonomy_id AS type_id
										FROM {$wpdb->posts} p 
										LEFT JOIN {$wpdb->term_relationships} trv ON p.ID = trv.object_id AND trv.term_taxonomy_id = {$variable->term_id}
										{$value['join']}
										{$joinAdd}
										{$joinAttributes}
										WHERE p.post_type = 'product' {$postIds}
										AND p.ID NOT IN (SELECT DISTINCT p.post_parent 
										FROM {$wpdb->posts} p {$value['joinVariation']}{$value['whereVariation']} 
										WHERE p.post_type = 'product_variation') 
										{$value['where']}
										GROUP BY p.ID 
										{$value['having']}";
								$selectVariation =
									"SELECT p.post_parent, '' as attributes, '' AS type_id 
										FROM {$wpdb->posts} p 
										{$value['joinVariation']}
							            {$joinAdd}
										WHERE p.post_type = 'product_variation'
										{$value['whereVariation']}
										GROUP BY p.post_parent 
										{$value['having']}";
								if ( 'AND' === $logic ) {
									$select[] = "({$selectProduct} UNION {$selectVariation})";
								} else {
									$select[] = $selectProduct;
									$select[] = $selectVariation;
								}
							}

							if ( 'AND' === $logic ) {
								$sql                      = 'SELECT t.ID, attributes FROM ( ' . implode( ' UNION ALL ', $select ) . ") t GROUP BY t.ID HAVING COUNT(*) = {$countBlock}";
								$wpdb->wpf_prepared_query = $sql;
							} else {
								$wpdb->wpf_prepared_query = implode( ' UNION ', $select );
							}
						}

						if ( '' !== $wpdb->wpf_prepared_query ) {
							$termProducts = $wpdb->get_results( $wpdb->wpf_prepared_query );
							if ( ! empty( $termProducts ) ) {
								$postsIn = array();
								foreach ( $termProducts as $product ) {
									$checkVariation = true;
									if ( (int) $product->type_id === $variable->term_id && '' !== $product->attributes ) {
										$productAttributes = unserialize( $product->attributes );
										if ( ! empty( $productAttributes ) ) {
											$checkVariation = ( 'AND' === $logic ) ? true : false;
											foreach ( $taxonomies as $taxonomy ) {
												$validCurrentVariation = ( 0 === $productAttributes[ $taxonomy ]['is_variation'] );
												if ( 'AND' === $logic ) {
													if ( ! $validCurrentVariation ) {
														$checkVariation = false;
													}
												} else {
													if ( $validCurrentVariation ) {
														$checkVariation = true;
													}
												}
											}
										}
									}

									if ( $checkVariation ) {
										$postsIn[] = $product->ID;
									}
								}
							}
							if ( ! empty( $postsIn ) ) {
								$args['post__in'] = $postsIn;
							} else {
								$args['post__in'] = array( 0 );
							}
						}
					}
				}
			}
		}
		$args = DispatcherWpf::applyFilters( 'checkBeforeFiltersFrontendArgs', $args, $filterSettings, $urlQuery );
		return $args;
	}


	public function getWcAttributeTaxonomies() {
		if (is_null($this->wcAttributes)) {
			$allAttributes = wc_get_attribute_taxonomies();
			if (!empty($allAttributes)) {
				$allAttributes = array_column($allAttributes, 'attribute_name');
				$allAttributes = array_map(function ( $attribute ) {
					return 'pa_' . $attribute;
				}, $allAttributes);
			} else {
				$allAttributes = array();
			}
			$this->wcAttributes = $allAttributes;
		}
		return $this->wcAttributes;
	}

	public function getRenderMode( $id, $settings, $isWidget = true ) {
		if (!isset($this->renderModes[$id])) {
			if ( isset( $settings['settings'] ) ) {
				$settings = $settings['settings'];
			}
			$displayOnPageShortcode = $this->getFilterSetting( $settings, 'display_on_page_shortcode', false );
			$displayShop            = ( $displayOnPageShortcode ) ? false : ! $isWidget;
			$displayCategory        = false;
			$displayTag             = false;
			$displayAttribute       = false;
			$displayMobile          = true;
			$displayProduct         = false;

			if (is_admin()) {
				$displayShop = true;
			} else {
				$displayOnPage = empty($settings['display_on_page']) ? 'shop' : $settings['display_on_page'];

				if ('specific' === $displayOnPage) {
					$pageList = empty($settings['display_page_list']) ? '' : $settings['display_page_list'];
					if (is_array($pageList)) {
						$pageList = isset($pageList[0]) ? $pageList[0] : '';
					}
					$pages = explode(',', $pageList);
					$pageId = $this->getView()->wpfGetPageId();
					if (in_array($pageId, $pages)) {
						$displayShop = true;
						$displayCategory = true;
						$displayTag = true;
					}
				} elseif ('custom_cats' === $displayOnPage) {
					$catList = empty($settings['display_cat_list']) ? '' : $settings['display_cat_list'];
					if (is_array($catList)) {
						$catList = isset($catList[0]) ? $catList[0] : '';
					}

					$cats      = explode(',', $catList);

					$displayChildCat = $this->getFilterSetting($settings, 'display_child_cat', false);
					if ($displayChildCat) {
						$catChild = array();
						foreach ($cats as $cat) {
							$catChild = array_merge($catChild, get_term_children( $cat, 'product_cat' ));
						}
						$cats = array_merge($cats, $catChild);
					}

					$parent_id = get_queried_object_id();
					if (in_array($parent_id, $cats)) {
						$displayCategory = true;
					}
				} elseif ( is_shop() || is_product_category() || is_product_tag() || is_customize_preview() ) {
					if ( 'shop' === $displayOnPage || 'both' === $displayOnPage ) {
						$displayShop = true;
					}
					if ( 'category' === $displayOnPage || 'both' === $displayOnPage ) {
						$displayCategory = true;
					}
					if ( 'tag' === $displayOnPage || 'both' === $displayOnPage ) {
						$displayTag = true;
					}
				} elseif ( is_tax() && ( 'both' === $displayOnPage || 'shop' === $displayOnPage ) ) {
					$displayAttribute = true;
				} elseif ('product' === $displayOnPage) {
					$displayProduct = true;
				}

				$displayFor = empty($settings['display_for']) ? '' : $settings['display_for'];

				$mobileBreakpointWidth = $this->getView()->getMobileBreakpointValue($settings);
				if ($mobileBreakpointWidth) {
					$displayFor = 'both';
				}
				if ('mobile' === $displayFor) {
					$displayMobile = UtilsWpf::isMobile();
				} else if ('both' === $displayFor) {
					$displayMobile = true;
				} else if ('desktop' === $displayFor) {
					$displayMobile = !UtilsWpf::isMobile();
				}
			}
			$hideWithoutProducts = !empty($settings['hide_without_products']) && $settings['hide_without_products'];
			$displayMode = $this->getDisplayMode();
			$mode = 0;

			if ( !$hideWithoutProducts || 'subcategories' != $displayMode || is_search()) {
				if ( is_product_category() && $displayCategory && $displayMobile ) {
					$mode = 1;
				} else if ( $this->isWcVendorsPluginActivated() && WCV_Vendors::is_vendor_page() && $displayShop && $displayMobile ) {
					$mode = 7;
				} else if ( is_shop() && $displayShop && $displayMobile ) {
					$mode = 2;
				} else if ( is_product_tag() && $displayTag && $displayMobile ) {
					$mode = 3;
				} else if ( is_tax('product_brand') && $displayShop && $displayMobile ) {
					$mode = 4;
				} else if ( is_tax('pwb-brand') && $displayShop && $displayMobile ) {
					$mode = 5;
				} else if ( $displayAttribute && $displayMobile ) {
					$mode = 6;
				} else if ( $displayShop && $displayMobile && !is_product_category() && !is_product_tag() ) {
					$mode = 10;
				} else if ( is_product() && $displayProduct && $displayMobile) {
					$mode = 8;
				}

			}
			$this->renderModes[$id] = $mode;
		}
		return $this->renderModes[$id];
	}
	private function wpf_get_loop_prop( $prop ) {
		return isset( $GLOBALS['woocommerce_loop'], $GLOBALS['woocommerce_loop'][ $prop ] ) ? $GLOBALS['woocommerce_loop'][ $prop ] : '';
	}

	public function getDisplayMode() {
		if (is_null($this->displayMode)) {
			$mode = '';
			if ( $this->wpf_get_loop_prop('is_search') || $this->wpf_get_loop_prop('is_filtered') ) {
				$display_type = 'products';
			} else {
				$parent_id    = 0;
				$display_type = '';
				if ( is_shop() ) {
					$display_type = get_option('woocommerce_shop_page_display', '');
				} elseif ( is_product_category() ) {
					$parent_id    = get_queried_object_id();
					$display_type = get_term_meta( $parent_id, 'display_type', true );
					$display_type = '' === $display_type ? get_option('woocommerce_category_archive_display', '') : $display_type;
				}

				if ( ( !is_shop() || 'subcategories' !== $display_type ) && 1 < $this->wpf_get_loop_prop('current_page') ) {
					$display_type = 'products';
				}
			}

			if ( '' === $display_type || ! in_array($display_type, array('products', 'subcategories', 'both'), true) ) {
				$display_type = 'products';
			}

			if ( in_array( $display_type, array('subcategories', 'both'), true) ) {
				$subcategories = woocommerce_get_product_subcategories( $parent_id );

				if (empty($subcategories)) {
					$display_type = 'products';
				}
			}
			$this->displayMode = $display_type;
		}
		return $this->displayMode;
	}

	public function addClausesTitleOrder( $args ) {
		global $wpdb;
		$posId = strpos($args['orderby'], '.product_id');
		if (false !== $posId) {
			$idBegin = strrpos( $args['orderby'], ',', ( strlen($args['orderby']) - $posId ) * ( -1 ) );
			if ($idBegin) {
				$args['orderby'] = substr($args['orderby'], 0, $idBegin);
			}
		} else {
			$posId = strpos($args['orderby'], $wpdb->posts . '.ID');
			if (false !== $posId) {
				$idBegin = strrpos($args['orderby'], ',', ( strlen($args['orderby']) - $posId ) * ( -1 ) );
				if ($idBegin) {
					$args['orderby'] = substr($args['orderby'], 0, $idBegin);
				}
			}
		}

		$order = $this->getWpfOrderParam(ReqWpf::getVar('wpf_order'));
		$orderByTitle = "$wpdb->posts.post_title $order";
		$args['orderby'] = ( empty($args['orderby']) ? $orderByTitle : $orderByTitle . ', ' . $args['orderby'] );
		remove_filter('posts_clauses', array($this, 'addClausesTitleOrder'));
		return $args;
	}

	public function addCustomOrder( $args, $customOrder = 'title' ) {
		if (empty($args['orderby'])) {
			$args['orderby'] = $customOrder;
			$args['order'] = 'ASC';
		} else if ($args['orderby'] != $customOrder) {
			if (is_array($args['orderby'])) {
				reset($args['orderby']);
				$key = key($args['orderby']);
				$args['orderby'] = array($key => $args['orderby'][$key]);
			} else {
				$args['orderby'] = array($args['orderby'] => empty($args['order']) ? 'ASC' : $args['order']);
			}
			$args['orderby'][$customOrder] = 'ASC';
			$args['order'] = '';
		}
		return $args;
	}

	private function getWpfOrderParam( $wpfOrder ) {
		$order = 'ASC';
		if ('titled' == $wpfOrder) {
			$order = 'DESC';
		}

		return $order;
	}

	/**
	 * Group together wp_query taxonomies params args with the same taxonomy name
	 *
	 * @param array $taxQuery
	 *
	 * @return array
	 */
	public function groupTaxQueryArgs( $taxQuery ) {
		if ( empty($taxQuery) || !is_array($taxQuery) ) {
			return $taxQuery;
		}

		$taxGroupedList = array(
			'product_cat',
			'product_tag'
		);

		$attributesTax = array_keys(wp_list_pluck(wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name'));

		if ($attributesTax) {
			$attributesTax = array_map(
				function( $tax) {
					return 'pa_' . $tax;
				},
				$attributesTax
			);

			$taxGroupedList = array_merge($taxGroupedList, $attributesTax);
		}

		$groupedTaxQueryVal = array();
		$taxQueryFormat = array();
		$uniq = array();
		foreach ($taxQuery as $taxQueryIndex => $taxQueryValue) {
			if (!empty($taxQueryValue['taxonomy']) && in_array($taxQueryValue['taxonomy'], $taxGroupedList)) {
				$group = $taxQueryValue['taxonomy'];
				if ( 'product_cat' != $group && 'product_tag' != $group ) {
					$group = 'product_att';
				}
				$groupedTaxQueryVal[$group][] = $taxQueryValue;
			} else if (!empty($taxQueryValue['wpf_group'])) {
				$group = $taxQueryValue['wpf_group'];
				foreach ($taxQueryValue as $wpfIndex => $wpfValue) {
					if (is_int($wpfIndex)) {
						$groupedTaxQueryVal[$group][] = $wpfValue;
					}
				}
			} else {
				$json = json_encode($taxQueryValue);
				if (!in_array($json, $uniq)) {
					if (is_int($taxQueryIndex)) {
						$taxQueryFormat[] = $taxQueryValue;
					} else {
						$taxQueryFormat[$taxQueryIndex] = $taxQueryValue;
					}
					$uniq[] = $json;
				}
			}
		}
		if ($groupedTaxQueryVal) {
			$logic = ReqWpf::getVar('filter_tax_block_logic');
			$logic = is_null($logic) ? 'AND' : strtoupper($logic);
			foreach ($groupedTaxQueryVal as $group => $values) {
				if (count($values) > 1) {
					$uniq = array();
					$vals = array();
					foreach ($values as $i => $v) {
						$json = json_encode($v);
						if (!in_array($json, $uniq)) {
							$vals[] = $v;
							$uniq[] = $json;
						}
					}
					$values = $vals;
				}
				$values['wpf_group'] = $group;
				$values['relation'] = $logic;
				$taxQueryFormat[] = $values;
			}
		}
		return $taxQueryFormat;
	}

	public function addAdminTab( $tabs ) {
		$tabs[ $this->getCode() . '#wpfadd' ] = array(
			'label' => esc_html__('Add New Filter', 'woo-product-filter'), 'callback' => array($this, 'getTabContent'), 'fa_icon' => 'fa-plus-circle', 'sort_order' => 10, 'add_bread' => $this->getCode(),
		);
		$tabs[ $this->getCode() . '_edit' ] = array(
			'label' => esc_html__('Edit', 'woo-product-filter'), 'callback' => array($this, 'getEditTabContent'), 'sort_order' => 20, 'child_of' => $this->getCode(), 'hidden' => 1, 'add_bread' => $this->getCode(),
		);
		$tabs[ $this->getCode() ] = array(
			'label' => esc_html__('Show All Filters', 'woo-product-filter'), 'callback' => array($this, 'getTabContent'), 'fa_icon' => 'fa-list', 'sort_order' => 20, //'is_main' => true,
		);
		return $tabs;
	}
	public function getCurrencyPrice( $raw_price, $dec = false ) {
		if (function_exists( 'alg_wc_currency_switcher_plugin' )) {
			return alg_wc_currency_switcher_plugin()->core->change_price_by_currency($raw_price);
		}

		$price = apply_filters('raw_woocommerce_price', $raw_price);

		// some plugin uses a different hook, use it if the standard one did not change the price
		if ( $price === $raw_price && ( is_plugin_active( 'woocommerce-currency-switcher/index.php' ) || is_plugin_active( 'woocommerce-multicurrency/woocommerce-multicurrency.php' ) ) ) {
			$price = apply_filters( 'woocommerce_product_get_regular_price', $raw_price, null );
		}

		return ( false === $dec ? $price : round($price, $dec) );
	}
	public function preparePriceFilter( $minPrice = null, $maxPrice = null, $rate = null ) {
		if ( !is_null($minPrice) ) {
			$minPrice = str_replace(',', '.', $minPrice);
			if ( !is_numeric($minPrice) ) {
				$minPrice = null;
			}
		}
		if ( !is_null($maxPrice) ) {
			$maxPrice = str_replace(',', '.', $maxPrice);
			if ( !is_numeric($maxPrice) ) {
				$maxPrice = null;
			}
		}

		if ( is_null($minPrice) && is_null($maxPrice) ) {
			return false;
		}

		list( $minPrice, $maxPrice ) = DispatcherWpf::applyFilters( 'priceTax', array(
			$minPrice,
			$maxPrice
		), 'subtract' );

		if (is_null($rate)) {
			$rate = $this->getCurrentRate();
		}
		$metaQuery = array('key' => '_price', 'price_filter' => true, 'type' => 'DECIMAL(20,3)');
		if (is_null($minPrice)) {
			$metaQuery['compare'] = '<=';
			$metaQuery['value'] = $maxPrice / $rate;
		} elseif (is_null($maxPrice)) {
			$metaQuery['compare'] = '>=';
			$metaQuery['value'] = $minPrice / $rate;
		} else {
			$metaQuery['compare'] = 'BETWEEN';
			$metaQuery['value'] = array($minPrice / $rate, $maxPrice / $rate);
		}
		add_filter('posts_where', array($this, 'controlDecimalType'), 9999, 2);

		return array('price_filter' => $metaQuery);
	}
	public function controlDecimalType( $where ) {
		return preg_replace('/DECIMAL\([\d]*,[\d]*\)\(20,3\)/', 'DECIMAL(20,3)', $where);
	}

	public function getCurrentRate() {
		$price = 1000;
		$newPrice = $this->getCurrencyPrice($price);
		return $newPrice / $price;
	}
	public function addHiddenFilterQuery( $query ) {
		$hidden_term = get_term_by('name', 'exclude-from-catalog', 'product_visibility');
		if ($hidden_term) {
			$query[] = array(
				'taxonomy' => 'product_visibility',
				'field' => 'term_taxonomy_id',
				'terms' => array($hidden_term->term_taxonomy_id),
				'operator' => 'NOT IN'
			);
		}
		return $query;
	}
	public function getTabContent() {
		return $this->getView()->getTabContent();
	}
	public function getEditTabContent() {
		$id = ReqWpf::getVar('id', 'get');
		return $this->getView()->getEditTabContent( $id );
	}
	public function getEditLink( $id, $tableTab = '' ) {
		$link = FrameWpf::_()->getModule('options')->getTabUrl( $this->getCode() . '_edit' );
		$link .= '&id=' . $id;
		if (!empty($tableTab)) {
			$link .= '#' . $tableTab;
		}
		return $link;
	}
	public function render( $params ) {
		return $this->getView()->renderHtml($params);
	}
	public function renderProductsList( $params ) {
		return $this->getView()->renderProductsListHtml($params);
	}
	public function renderSelectedFilters( $params ) {
		return FrameWpf::_()->isPro() ? $this->getView()->renderSelectedFiltersHtml($params) : '';
	}
	public function showAdminErrors() {
		// check WooCommerce is installed and activated
		if (!$this->isWooCommercePluginActivated()) {
			// WooCommerce install url
			$wooCommerceInstallUrl = add_query_arg(
				array(
					's' => 'WooCommerce',
					'tab' => 'search',
					'type' => 'term',
				),
				admin_url( 'plugin-install.php' )
			);
			$tableView = $this->getView();
			$tableView->assign('errorMsg',
				$this->translate('For work with "')	. WPF_WP_PLUGIN_NAME . $this->translate('" plugin, You need to install and activate WooCommerce plugin.')
			);
			// check current module
			if (ReqWpf::getVar('page') == WPF_SHORTCODE) {
				// show message
				HtmlWpf::echoEscapedHtml($tableView->getContent('showAdminNotice'));
			}
		}
	}
	public function isWooCommercePluginActivated() {
		return class_exists('WooCommerce');
	}

	public function WC_pif_product_has_gallery( $classes ) {
		global $product;

		$post_type = get_post_type( get_the_ID() );

		if ( wp_doing_ajax() ) {

			if ( 'product' == $post_type ) {

				if ( is_callable( 'WC_Product::get_gallery_image_ids' ) ) {
					$attachment_ids = $product->get_gallery_image_ids();
				} else {
					$attachment_ids = $product->get_gallery_attachment_ids();
				}

				if ( $attachment_ids ) {
					$classes[] = 'pif-has-gallery';
				}
			}
		}

		return $classes;
	}

	public function YITH_hide_add_to_cart_loop( $link, $product ) {

		if ( wp_doing_ajax() ) {

			if ( get_option( 'ywraq_hide_add_to_cart' ) == 'yes' ) {
				return call_user_func_array(array('YITH_YWRAQ_Frontend', 'hide_add_to_cart_loop'), array($link, $product));
			}
		}

		return $link;
	}

	/**
	 * Add plugin compatibility wp_query filtering results args
	 *
	 * @link https://iconicwp.com/products/woocommerce-show-single-variations
	 *
	 * @param array $args query args
	 *
	 * @return array
	 */
	public function Iconic_Wssv_Query_Args( $args ) {
		$args = Iconic_WSSV_Query::add_variations_to_shortcode_query($args, array());

		return $args;
	}

	public function getAttributeTerms( $slug ) {
		$terms = array();
		if (empty($slug)) {
			return $terms;
		}
		$args = array('hide_empty' => false);

		if (is_numeric($slug)) {
			$values = get_terms(wc_attribute_taxonomy_name_by_id((int) $slug), $args);
		} else {
			$values = DispatcherWpf::applyFilters('getCustomTerms', array(), $slug, $args);
		}

		if ($values) {
			foreach ($values as $value ) {
				if (!empty($value->term_id)) {
					$terms[$value->term_id] = $value->name;
				}
			}
		}

		return $terms;
	}

	public function getFilterTaxonomies( $settings, $calcCategories = false, $filterSettings = array(), $ajax = false ) {
		$taxonomies           = array();
		$forCount             = array();
		$forCountWithChildren = array();
		$other                = array();

		if ( $calcCategories ) {
			$taxonomies[] = 'product_cat';
		}
		$key = 0;
		foreach ( $settings as $filter ) {
			if ( empty( $filter['settings']['f_enable'] ) ) {
				continue;
			}

			$taxonomy = '';
			switch ( $filter['id'] ) {
				case 'wpfCategory':
					$taxonomy = 'product_cat';
					break;
				case 'wpfTags':
					$taxonomy = 'product_tag';
					break;
				case 'wpfAttribute':
					if ( ! empty( $filter['settings']['f_list'] ) ) {
						$slug = $filter['settings']['f_list'];
						if ( 'custom_meta_field_check' === $slug ) {
							$other[] = $slug;
						} else {
							$taxonomy = ( is_numeric( $slug ) )
								? wc_attribute_taxonomy_name_by_id( (int) $slug )
								: DispatcherWpf::applyFilters( 'getCustomAttributeName', $slug );
						}
					}
					break;
				case 'wpfBrand':
					$taxonomy = 'product_brand';
					break;
				case 'wpfPerfectBrand':
					$taxonomy = 'pwb-brand';
					break;
				case 'wpfPrice':
				case 'wpfPriceRange':
					if ( ! $ajax || ( isset( $filterSettings['filter_recount_price'] ) && $filterSettings['filter_recount_price'] ) ) {
						$other[] = $filter['id'];
					}
					break;
				case 'wpfAuthor':
				case 'wpfVendors':
				case 'wpfRating':
					$other[] = $filter['id'];
					break;
				default:
					break;
			}

			if ( ! empty( $taxonomy ) ) {
				$taxonomies[ $key ] = $taxonomy;
				if ( ! empty( $filter['settings']['f_show_count'] ) ) {
					$forCount[] = $taxonomy;
				}
				if ( ! empty( $filter['settings']['f_show_count_parent_with_children'] ) ) {
					$forCountWithChildren[] = $taxonomy;
				}
			}
			$key ++;

		}

		$getNames = ( ! $ajax && $this->getFilterSetting( $filterSettings['settings'], 'check_get_names', 0 ) )
			? $this->checkGetNames( $taxonomies, $other )
			: array();


		return array(
			'names'               => array_unique( $taxonomies ),
			'count'               => array_unique( $forCount ),
			'count_with_children' => array_unique( $forCountWithChildren ),
			'other_names'         => $other,
			'get_names'           => $getNames,
		);
	}

	/**
	 * Forms an array with names from the address bar
	 *
	 * @param $taxonomies
	 *
	 * @return array
	 */
	public function checkGetNames( &$taxonomies, &$other ) {
		$blocks   = array();
		$getNames = array();
		foreach ( $taxonomies as $index => $taxonomy ) {
			switch ( $taxonomy ) {
				case 'product_cat':
					$blocks[ $taxonomy ][] = 'filter_cat.*?_' . $index;
					break;
				case 'product_tag':
					$blocks[ $taxonomy ][] = 'product_tag_' . $index;
					break;
				case 'pwb-brand':
					$blocks[ $taxonomy ][] = 'filter_pwb.*?_' . $index;
					break;
				default:
					if ( strpos( $taxonomy, 'pa_' ) === 0 ) {
						$blocks[ $taxonomy ][] = 'filter_' . substr( $taxonomy, 3 );
					} else {
						$blocks[ $taxonomy ][] = 'filter_' . $taxonomy;
					}
					break;
			}
		}

		foreach ( $other as $index => $taxonomy ) {
			switch ( $taxonomy ) {
				case 'custom_meta_field_check':
					$blocks[ $taxonomy ][0] = 'f_meta_.+?';
					break;
				case 'wpfRating':
					$blocks[ $taxonomy ][] = 'pr_rating';
					break;
			}
		}

		if ( ! empty( $blocks ) ) {
			$getGet = ReqWpf::get( 'get' );
			foreach ( $getGet as $param => $value ) {
				foreach ( $blocks as $taxanomy => $patterns ) {
					foreach ( $patterns as $pattern ) {
						preg_match( '/' . $pattern . '/', $param, $matches );
						if ( isset( $matches[0] ) ) {
							$getNames[ $taxanomy ][] = $param;
							$index                   = array_search( $taxanomy, $taxonomies, true );
							if ( is_numeric( $index ) ) {
								unset( $taxonomies[ $index ] );
							} else {
								$index = array_search( $taxanomy, $other, true );
								if ( is_numeric( $index ) ) {
									unset( $other[ $index ] );
								}
							}
						}
					}
				}
			}
		}

		return $getNames;

	}


	/**
	 * Get filter existing individual filters items
	 *
	 * @param int | null $args wp_query args
	 * @param array $taxonomies
	 * @param int | null $calcCategory
	 * @param int | bool $prodCatId
	 * @param array $generalSettings
	 * @param bool $ajax
	 * @param array $currentSettings
	 *
	 * @return mixed
	 */
	public function getFilterExistsItems( $args, $taxonomies, $calcCategory = null, $prodCatId = false, $generalSettings = array(), $ajax = false, $currentSettings = array() ) {

		if ( empty( $taxonomies['names'] ) && empty( $taxonomies['other_names'] ) && empty( $taxonomies['get_names'] ) ) {
			return false;
		}

		$calc       = array();
		$isGetNames = ! empty( $taxonomies['get_names'] );

		if ( ! empty( $taxonomies['names'] ) || ! empty( $taxonomies['other_names'] ) ) {
			list($args, $argsFiltered) = $this->getArgsWCQuery( $args, $currentSettings );
			if ( $isGetNames ) {
				$calc = array( 'full' => $argsFiltered );
			} else {
				$calc = ( empty( $argsFiltered ) ) ? array( 'full' => $args ) : array( 'full' => $argsFiltered, 'light' => $args );
			}
		}

		if ( ! empty( $taxonomies['get_names'] ) ) {
			foreach ( $taxonomies['get_names'] as $taxonomy => $params ) {
				foreach ( $params as $param ) {
					$argsFiltered   = $this->getQueryVars( $args, $param );
					$calc[ $param ] = array( 'args' => $argsFiltered, 'taxonomy' => $taxonomy );
				}
			}
		}

		$result           = array( 'exists' => array() );

		foreach ( $calc as $mode => $args ) {

			if ( isset( $args['args'] ) ) {
				$taxonomy = [ $args['taxonomy'] ];
				$args     = $args['args'];
			} else {
				$taxonomy = $taxonomies['names'];
			}

			$param = array(
				'ajax'            => $ajax,
				'prodCatId'       => $prodCatId,
				'generalSettings' => $generalSettings,
				'currentSettings' => $currentSettings,
			);
			$args  = $this->addArgs( $args, $param );

			$isCalcCategory = ! is_null( $calcCategory );

			$param = array(
				'isCalcCategory'       => $isCalcCategory,
				'calcCategory'         => $calcCategory,
				'taxonomy'             => $taxonomy,
				'generalSettings'      => $generalSettings,
				'mode'                 => $mode,
				'forCount'             => $taxonomies['count'],
				'forCountWithChildren' => $taxonomies['count_with_children'],
				'withCount'            => ( ! empty( $taxonomies['count'] ) || $isCalcCategory ),
				'isInStockOnly'        => ( get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) === 'yes' ),
				'currentSettings'      => $currentSettings,
				'ajax'                 => $ajax,
			);
			//$args['orderby'] = 'none';

			$filterLoop = new WP_Query( $args );

			list( $productList, $existTerms, $calcCategories ) = $this->getTerms( $filterLoop, $param, $result['exists'] );

			switch ( $mode ) {
				case 'full':
					$result['exists']     = $existTerms;
					$result['categories'] = $calcCategories;
					break;
				case 'light':
					$result['all'] = $existTerms;
					break;
				default:
					if ( ! empty( $existTerms ) ) {
						$result['exists'] = array_merge( $result['exists'], $existTerms );
					}
					break;
			}

			if ( ( 'full' === $mode && ! key_exists( 'light', $calc ) ) || 'light' === $mode ) {
				$param  = array(
					'productList'     => $productList,
					'generalSettings' => $generalSettings,
					'taxonomies'      => $taxonomies,
					'ajax'            => $ajax
				);
				$result = array_merge( $result, $this->getExistsMore( $args, $param ) );
			}
		}

		return $result;
	}

	/**
	 * Returns previously stored arguments in an object
	 *
	 * @param $args
	 *
	 * @return array
	 */
	public function getArgsWCQuery( $args, $currentSettings ) {
		$argsFiltered      = '';
		$postType          = '';
		$doNotUseShortcode = $this->getFilterSetting( $currentSettings, 'do_not_use_shortcut', false );

		if ( is_null( $args ) ) {
			$filterId  = $this->currentFilterId;
			$filterKey = $this->shortcodeFilterKey . $filterId;
			$existSC   = ( count( $this->shortcodeWCQuery ) > 0 );
			if ( ! $doNotUseShortcode && ! isset( $this->shortcodeWCQuery[ $filterKey ] ) ) {
				$filterKey = '-';
			}
			if ( $existSC && isset( $this->shortcodeWCQuery[ $filterKey ] ) ) {
				$args         = $this->shortcodeWCQuery[ $filterKey ];
				$argsFiltered = isset( $this->shortcodeWCQueryFiltered[ $filterKey ] ) ? $this->shortcodeWCQueryFiltered[ $filterKey ] : '';
				$postType     = isset( $args['post_type'] ) ? $args['post_type'] : '';
			}
			if ( 'product' != $postType && ( ! is_array( $postType ) || ! in_array( 'product', $postType ) ) ) {
				$args         = $this->mainWCQuery;
				$argsFiltered = $this->mainWCQueryFiltered;
				$postType     = isset( $args['post_type'] ) ? $args['post_type'] : '';
				if ( 'product' !== $postType && ( ! is_array( $postType ) || ! in_array( 'product', $postType, true ) ) ) {
					if ( $existSC ) {
						$args         = reset( $this->shortcodeWCQuery );
						$argsFiltered = reset( $this->shortcodeWCQueryFiltered );
						$postType     = isset( $args['post_type'] ) ? $args['post_type'] : '';
					}
				}
			}
			if ( 'product' !== $postType && ( ! is_array( $postType ) || ! in_array( 'product', $postType, true ) ) ) {
				$q = new WP_Query( DispatcherWpf::applyFilters( 'beforeFilterExistsTermsWithEmptyArgs', array( 'post_type'  => 'product', 'meta_query' => array(), 'tax_query' => array() ) ) );
				$this->loadProductsFilter( $q );
				$args         = $this->mainWCQuery;
				$argsFiltered = $this->mainWCQueryFiltered;
			}

			if ( $doNotUseShortcode && 'product' !== $postType && ( ! is_array( $postType ) || ! in_array( 'product', $postType, true ) ) ) {
				$filterKey = '-';
				if ( $existSC && isset( $this->shortcodeWCQuery[ $filterKey ] ) ) {
					$args         = $this->shortcodeWCQuery[ $filterKey ];
					$argsFiltered = isset( $this->shortcodeWCQueryFiltered[ $filterKey ] ) ? $this->shortcodeWCQueryFiltered[ $filterKey ] : '';
				}
			}
		}

		return array($args, $argsFiltered);
	}

	/**
	 * Adds arguments to $args array
	 *
	 * @param $args
	 * @param $param
	 *
	 * @return array
	 */
	public function addArgs( $args, $param ) {
		if ( isset( $args['taxonomy'] ) ) {
			unset( $args['taxonomy'], $args['term'] );
		}

		if ( is_null( $args ) || empty( $args ) || 'product' !== $args['post_type'] && ( is_array( $args['post_type'] ) && ! in_array( 'product', $args['post_type'], true ) ) ) {
			$args = array(
				'post_status'         => 'publish',
				'post_type'           => 'product',
				'ignore_sticky_posts' => true,
				'tax_query'           => array(),
			);
		}

		$args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => 'exclude-from-catalog',
			'operator' => 'NOT IN',
		);

		if ( $param['prodCatId'] ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $param['prodCatId'],
			);
		}

		$args['nopaging']       = true;
		$args['posts_per_page'] = - 1;
		$args['hide_empty']     = 1;
		$args['fields']         = 'ids';

		if ( class_exists( 'Iconic_WSSV_Query' ) ) {
			$args = $this->Iconic_Wssv_Query_Args( $args );
		}

		//Integration with AJAX Search for WooCommerce

		/*
		* Plugin URL: https://wordpress.org/plugins/ajax-search-for-woocommerce/
		* Author: Damian Góra
		*/
		if ( class_exists( 'DGWT_WC_Ajax_Search' ) ) {
			$searchIds = apply_filters( 'dgwt/wcas/search_page/result_post_ids', array() );
			if ( $searchIds && is_array($searchIds) ) {
				$postIds = isset($args['post__in']) ? $args['post__in'] : '';
				if ( is_array($postIds) && !empty($postIds) ) {
					if ( 1 !== count($postIds) || 0 !== $postIds[0] ) {
						$args['post__in'] = array_intersect($postIds, $searchIds);
					}
				} else {
					$args['post__in'] = $searchIds;
				}
				$args['s'] = '';
			}
		}

		if ( ! empty( $args['post__in'] ) && ( 'product' === $args['post_type'] ) ) {
			$args['post_type'] = array( 'product', 'product_variation' );
		}

		$args = $this->addWooOptions($args);

		foreach ( $param['generalSettings'] as $filter ) {
			$settings = $filter['settings'];
			$hiddens  = array( 'f_hidden_brands', 'f_hidden_categories', 'f_hidden_attributes', 'f_hidden_tags' );
			$replace  = false;
			foreach ( $hiddens as $hidden ) {
				if ( $this->getFilterSetting( $settings, $hidden ) ) {
					$replace = true;
				}
			}
			if ( $replace ) {
				foreach ( $args['tax_query'] as &$tax ) {
					if ( isset ( $tax['wpf_group'] ) && $tax['wpf_group'] === $filter['name'] && isset( $tax[0]['terms'] ) ) {
						$tax[0]['terms'] = $settings['f_mlist[]'];
					}
				}
			}
		}

		return DispatcherWpf::applyFilters( 'addFilterExistsItemsArgs', $args );
	}

	/**
	 * Returns items in filter blocks
	 *
	 * @param $filterLoop
	 * @param $param
	 *
	 * @return array
	 */
	public function getTerms( $filterLoop, $param, $existTerms ) {
		$productList    = '';
		$calcCategories = array();
		$childs         = array();
		$names          = array();

		if ( $filterLoop->have_posts() ) {
			$ids          = $filterLoop->posts;

			if (count($ids) < 1000) {
				$productList  = implode( ',', $ids );
			} else {
				$productList = $filterLoop->request;
				$orderPos = strpos($productList, 'ORDER');
				if ($orderPos) {
					$productList = substr($productList, 0, $orderPos);
				}
			}
			$taxonomyList = "'" . implode( "','", $param['taxonomy'] ) . "'";
			global $wpdb;
			$sql = 'SELECT ' . ( $param['withCount'] ? '' : 'DISTINCT ' ) . 'tr.term_taxonomy_id, tt.term_id, tt.taxonomy, tt.parent' . ( $param['withCount'] ? ', COUNT(*) as cnt' : '' ) . ' FROM ' . $wpdb->term_relationships . ' tr INNER JOIN ' . $wpdb->term_taxonomy . ' tt ON (tt.term_taxonomy_id=tr.term_taxonomy_id) ' . ( $param['withCount'] && $param['isInStockOnly']
					? 'INNER JOIN ' . $wpdb->postmeta . ' pm ON (pm.post_id=tr.object_id) '
					: '' ) . ' WHERE tr.object_id in (' . $productList . ') AND tt.taxonomy IN (' . $taxonomyList . ')' . ( $param['withCount'] && $param['isInStockOnly'] ? ' AND pm.meta_key=\'_stock_status\' AND pm.meta_value != \'outofstock\'' : '' );
			if ( $param['withCount'] ) {
				$sql .= ' GROUP BY tr.term_taxonomy_id';
			}

			$sql = DispatcherWpf::applyFilters( 'addCustomAttributesSql', $sql, array(
				'taxonomies'      => $param['taxonomy'],
				'withCount'       => $param['withCount'],
				'productList'     => $productList,
				'generalSettings' => $param['generalSettings'],
				'currentSettings' => $param['currentSettings']
			) );
			$wpdb->wpf_prepared_query = $sql;
			$termProducts = $wpdb->get_results( $wpdb->wpf_prepared_query );

			$orders                    = $param['generalSettings'];
			$customLocalAttributeNames = array();
			$termProductsLocalAttr     = array();
			foreach ( $orders as $order ) {
				$customLocalAttribute = $this->getFilterSetting($order['settings'], 'f_custom_local_attribute', '');
				if ( '' !== $customLocalAttribute ) {
					$correctName = ( function_exists( 'mb_strtolower' ) )
						? mb_strtolower( $customLocalAttribute )
						: strtolower( $customLocalAttribute );
					$customLocalAttributeNames[] = preg_replace( '/\s/', '-', $correctName );
				}
			}
			if ( ! empty( $customLocalAttributeNames ) ) {
				foreach ( $termProducts as $key => $term ) {
					if ( 'f_meta__product_attributes' === $term->taxonomy ) {
						$localAttributes = unserialize( $term->term_taxonomy_id );
						foreach ( $customLocalAttributeNames as $customLocalAttributeName ) {
							if ( key_exists( $customLocalAttributeName, $localAttributes ) && '' !== $localAttributes[ $customLocalAttributeName ]['value'] ) {
								$isNewTerm = true;
								if ( key_exists( $customLocalAttributeName, $termProductsLocalAttr ) ) {
									foreach ( $termProductsLocalAttr[ $customLocalAttributeName ] as &$temp ) {
										if ( $temp->term_id === $localAttributes[ $customLocalAttributeName ]['value'] ) {
											$temp->cnt ++;
											$isNewTerm = false;
										}
									}
								}
								if ( $isNewTerm ) {
									$newTerm                                           = new \stdClass();
									$newTerm->cnt                                      = 1;
									$newTerm->taxonomy                                 = 'f_local_' . $customLocalAttributeName;
									$newTerm->term_id                                  = $localAttributes[ $customLocalAttributeName ]['value'];
									$termProductsLocalAttr[ $customLocalAttributeName ][] = $newTerm;
								}
							}
						}
						unset( $termProducts[ $key ] );
					}
				}
			}

			if ( ! empty( $termProductsLocalAttr ) ) {
				foreach ( $termProductsLocalAttr as $level1 ) {
					foreach ( $level1 as $level2 ) {
						$termProducts[] = $level2;
					}
				}
			}


			foreach ( $termProducts as $term ) {
				$taxonomy = $term->taxonomy;
				$isCat    = 'product_cat' === $taxonomy;

				$name           = urldecode( $taxonomy );
				$names[ $name ] = $taxonomy;
				if ( ! isset( $existTerms[ $name ] ) ) {
					$existTerms[ $name ] = array();
				}

				$termId                         = $term->term_id;
				$cnt                            = $param['withCount'] ? intval( $term->cnt ) : 0;
				$existTerms[ $name ][ $termId ] = $cnt;

				if ( $param['ajax'] && 0 === strpos( $taxonomy, 'f_meta_' ) ) {
					$existTerms[ $name ]['relation'][$termId] = $term->term_taxonomy_id;
				}

				$parent = (int) $term->parent;
				if ( $isCat && $param['isCalcCategory'] && $param['calcCategory'] === $parent ) {
					$calcCategories[ $termId ] = $cnt;
				}

				if ( 0 !== (int) $parent ) {
					$children = array( $termId );
					do {
						if ( ! isset( $existTerms[ $name ][ $parent ] ) ) {
							$existTerms[ $name ][ $parent ] = 0;
						}
						if ( isset( $childs[ $parent ] ) ) {
							array_merge( $childs[ $parent ], $children );
						} else {
							$childs[ $parent ] = $children;
						}
						$parentTerm = get_term( $parent, $taxonomy );
						$children[] = $parent;
						if ( $parentTerm && isset( $parentTerm->parent ) ) {
							$parent = $parentTerm->parent;
							if ( $isCat && $param['isCalcCategory'] && $param['calcCategory'] === $parent ) {
								$calcCategories[ $parentTerm->term_id ] = 0;
							}
						} else {
							$parent = 0;
						}
					} while ( 0 !== $parent );
				}
			}

			if ( 'full' === $param['mode'] && $param['withCount'] ) {
				foreach ( $existTerms as $taxonomy => $terms ) {
					$allCalc          = in_array( $taxonomy, $param['forCount'], true );
					$calcWithChildren = in_array( $taxonomy, $param['forCountWithChildren'], true );
					if ( ! ( $allCalc || ( $param['isCalcCategory'] && 'product_cat' === $taxonomy ) || $calcWithChildren ) ) {
						continue;
					}
					foreach ( $terms as $termId => $cnt ) {
						if ( $calcWithChildren ) {
							$query                              = new WP_Query( array(
								'post__in'  => $ids,
								'tax_query' => array(
									array(
										'taxonomy'         => 'product_cat',
										'field'            => 'id',
										'terms'            => $termId,
										'include_children' => true,
									),
								),
								'nopaging'  => true,
								'fields'    => 'ids',
							) );
							$cnt                                = intval( $query->post_count );
							$existTerms[ $taxonomy ][ $termId ] = $cnt;
							if ( isset( $calcCategories[ $termId ] ) ) {
								$calcCategories[ $termId ] = $cnt;
							}
						} elseif ( empty( $cnt ) ) {
							if ( isset( $childs[ $termId ] ) && ( $allCalc || isset( $calcCategories[ $termId ] ) ) ) {
								$sql                                = "SELECT count(DISTINCT tr.object_id)
										FROM $wpdb->term_relationships tr
										INNER JOIN $wpdb->term_taxonomy tt ON (tt.term_taxonomy_id=tr.term_taxonomy_id)
										WHERE tr.object_id in (" . $productList . ")
										AND tt.taxonomy='" . $names[ $taxonomy ] . "'
										AND tt.term_id in (" . $termId . ',' . implode( ',', $childs[ $termId ] ) . ')';
								$wpdb->wpf_prepared_query           = $sql;
								$cnt                                = intval( $wpdb->get_var( $wpdb->wpf_prepared_query ) );
								$existTerms[ $taxonomy ][ $termId ] = $cnt;
								if ( isset( $calcCategories[ $termId ] ) ) {
									$calcCategories[ $termId ] = $cnt;
								}
							}
						}
					}
				}
			}
		}

		return array( $productList, $existTerms, $calcCategories );
	}

	/**
	 * Returns additional data on minimum and maximum prices and users
	 *
	 * @param $args
	 * @param $param
	 *
	 * @return mixed
	 */
	public function getExistsMore( $args, $param ) {
		global $wpdb;
		$result['existsPrices']              = new stdClass();
		$result['existsPrices']->wpfMinPrice = 1000000000;
		$result['existsPrices']->wpfMaxPrice = 0;
		$result['existsPrices']->decimal     = 0;
		$result['existsPrices']->dataStep    = '1';
		$result['existsUsers']               = array();

		if ( '' !== $param['productList'] && ! empty ( $param['taxonomies']['other_names'] ) ) {
			foreach ( $param['generalSettings'] as $setting ) {
				if ( in_array( $setting['id'], $param['taxonomies']['other_names'], true ) ) {
					switch ( $setting['id'] ) {
						case 'wpfPrice':
						case 'wpfPriceRange':
							$productListForPrice = $param['productList'];
							if ( isset( $args['meta_query'] ) ) {
								$issetArgsPrice = false;
								foreach ( $args['meta_query'] as $key => $row ) {
									if ( isset( $row['price_filter'] ) ) {
										$issetArgsPrice = true;
										unset ( $args['meta_query'][ $key ] );
									}
								}
								if ( $issetArgsPrice ) {
									$filterLoop = new WP_Query( $args );
									if ( $filterLoop->have_posts() ) {
										$productListForPrice = implode( ',', $filterLoop->posts );
									}
								}
							}
							if ( 'wpfPriceRange' === $setting['id'] ) {
								list( $result['existsPrices']->decimal, $result['existsPrices']->dataStep ) = DispatcherWpf::applyFilters( 'getDecimal', array(
									0,
									1
								), $setting['settings'] );
								$price = $this->getView()->wpfGetFilteredPriceFromProductList( $setting['settings'], $productListForPrice, false, $result['existsPrices']->decimal );
							} else {
								$price = $this->getView()->wpfGetFilteredPriceFromProductList( $setting['settings'], $productListForPrice, true );

							}
							if ( is_object( $price ) ) {
								$result['existsPrices']->wpfMinPrice = $price->wpfMinPrice;
								$result['existsPrices']->wpfMaxPrice = $price->wpfMaxPrice;
								if ( isset( $price->tax ) ) {
									$result['existsPrices']->tax = $price->tax;
								}
							}
							break;

						case 'wpfAuthor':
						case 'wpfVendors':
							if ( empty( $result['existsUsers'] ) ) {
								$result['existsUsers'] = dbWpf::get(
									'SELECT DISTINCT ' . $wpdb->users . '.`ID`
						FROM ' . $wpdb->posts . '
						JOIN ' . $wpdb->users . ' ON ' . $wpdb->posts . '.post_author = ' . $wpdb->users . '.`ID`
						WHERE ' . $wpdb->posts . '.`ID`	IN(' . $param['productList'] . ') ' );
							}
							break;

					}
				}
			}
		}

		return $result;
	}

	public function addAjaxFilterForYithWoocompare( $actions ) {
		return array_merge($actions, array('filtersFrontend'));
	}
	public function getAllPages() {
		global $wpdb;
		$allPages = dbWpf::get("SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'page' AND post_status IN ('publish','draft') ORDER BY post_title");
		$pages = array();
		if (!empty($allPages)) {
			foreach ($allPages as $p) {
				$pages[ $p['ID'] ] = $p['post_title'];
			}
		}
		return $pages;
	}
	
	public function isWcVendorsPluginActivated() {
		return class_exists('WC_Vendors');
	}

	/**
	 * Get logic for filtering.
	 *
	 * @return array
	 */
	public function getAttrFilterLogic( $mode = '' ) {
		$logic = array (
			'display' => array(
				'and' => 'And',
				'or'  => 'Or',
			),
			'loop' => array(
				'and' => 'AND',
				'or'  => 'IN',
			),
			'delimetr' => array(
				'and' => ',',
				'or'  => '|',
			)
		);

		$logic = DispatcherWpf::applyFilters( 'getAttrFilterLogic', $logic );
		return empty($mode) ? $logic : ( isset($logic[$mode]) ? $logic[$mode] : array() );
	}
	
	public function getFilterTagsList() {
		return array( 0 => 'Default', 1 => 'h1', 2 => 'h2', 3 => 'h3', 4 => 'h4', 5 => 'h5' );
	}
	
	public function getCategoriesDisplay() {
		$catArgs = array(
			'orderby' => 'name',
			'order' => 'asc',
			'hide_empty' => false,
		);
		
		$productCategories = get_terms( 'product_cat', $catArgs );
		$categoryDisplay = array();
		$parentCategories = array();
		foreach ($productCategories as $c) {
			if (0 == $c->parent) {
				array_push($parentCategories, $c->term_id);
			}
			$categoryDisplay[$c->term_id] = '[' . $c->term_id . '] ' . $c->name;
		}
		
		return array( $categoryDisplay, $parentCategories );
	}
	
	public function getTagsDisplay() {
		$tagArgs = array(
			'orderby' => 'name',
			'order' => 'asc',
			'hide_empty' => false,
			'parent' => 0
		);
		
		$productTags = get_terms('product_tag', $tagArgs);
		$tagsDisplay = array();
		foreach ($productTags as $t) {
			$tagsDisplay[$t->term_id] = $t->name;
		}
		
		return array( $tagsDisplay );
	}
	
	public function getAttributesDisplay() {
		$productAttr = DispatcherWpf::applyFilters('addCustomAttributes', wc_get_attribute_taxonomies());
		
		$attrDisplay = array(0 => esc_html__('Select...', 'woo-product-filter'));
		$attrTypes = array();
		$attrNames = array();
		foreach ($productAttr as $attr) {
			$attrId = (int) $attr->attribute_id;
			$slug = empty($attrId) ? $attr->attribute_slug : $attrId;
			$attrDisplay[$slug] = $attr->attribute_label;
			$attrTypes[$slug] = isset($attr->custom_type) ? $attr->custom_type : '';
			$attrNames[$slug] = isset($attr->filter_name) ? $attr->filter_name : 'filter_' . $attr->attribute_name;
		}
		
		return array( $attrDisplay, $attrTypes, $attrNames );
	}
	
	public function getRolesDisplay() {
		if (!function_exists('get_editable_roles')) {
			require_once(ABSPATH . '/wp-admin/includes/user.php');
		}
		$rolesMain = get_editable_roles();
		$roles = array();
		
		foreach ($rolesMain as $key => $r) {
			$roles[$key] = $r['name'];
		}
		
		return array( $roles );
	}

	/**
	 * Exlude parent terms from term list
	 *
	 * @param array $termList
	 * @param string $taxonomy
	 *
	 * @return array
	 */
	public function exludeParentTems( $termList, $taxonomy ) {
		foreach ($termList as $key => $termId) {
			$parents = get_ancestors( $termId, $taxonomy, 'taxonomy' );

			if (is_array($parents)) {
				// remove all parent termsId from main parent list
				foreach ($parents as $parentId) {
					if (array_search($parentId, $termList) !== false) {
						$keyParent = array_search($parentId, $termList);
						unset($termList[$keyParent]);
					}
				}
			}
		}

		return $termList;
	}

	/**
	 * Exlude parent terms from term list
	 *
	 * @param array $termList
	 * @param string $taxonomy
	 *
	 * @return array
	 */
	public function exludeChildTems( $termList, $taxonomy ) {
		foreach ($termList as $key => $termId) {
			$children = get_term_children( $termId, $taxonomy );
			if (is_array($children)) {
				// remove all parent termsId from main parent list
				foreach ($children as $childId) {
					if (array_search($childId, $termList) !== false) {
						$keyParent = array_search($childId, $termList);
						unset($termList[$keyParent]);
					}
				}
			}
		}

		return $termList;
	}

	/**
	 * Add shortcode attributes to additional html data attributes
	 *
	 * @param array $attributes
	 */
	public function addWoocommerceShortcodeQuerySettings( $attributes ) {
		$shortcodeAttr = htmlentities(UtilsWpf::jsonEncode($attributes));

		echo '<span class="wpfHidden" data-shortcode-attribute="' . esc_html($shortcodeAttr) . '"></span>';
	}

	public static function getProductsShortcode( $content ) {
		$shortcode_tags = array(
			'products' => 'WC_Shortcodes::products',
			//'product_categories' => 'WC_Shortcodes::product_categories',
		);

		if ( false === strpos( $content, '[' ) ) {
			return $content;
		}

		if ( empty( $shortcode_tags ) || ! is_array( $shortcode_tags ) ) {
			return $content;
		}

		preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches );
		$tagnames = array_intersect( array_keys( $shortcode_tags ), $matches[1] );

		if ( empty( $tagnames ) ) {
			return $content;
		}

		$pattern = get_shortcode_regex( $tagnames );
		preg_match_all( "/$pattern/", $content, $matches );
		if ( count( $matches ) > 3 ) {
			foreach ( (array) $matches[3] as $m ) {
				new WC_Shortcode_Products( shortcode_parse_atts( $m ), 'products' );
			}
		}

		return $content;
	}

	public function queryResults( $result ) {
		if ( 0 === $result->total ) {
			$options = FrameWpf::_()->getModule('options')->getModel('options')->getAll();
			if ( isset( $options['not_found_products_message'] ) && '1' === $options['not_found_products_message']['value'] ) {
				echo '<p class="woocommerce-info">' . esc_html__( 'No products were found matching your selection.', 'woocommerce' ) . '</p>';
			}
		}

		return $result;
	}

	public function getElementorClass( $data ) {
		$rawData = $data->get_raw_data();
		if ( isset( $rawData['settings']['_css_classes'] ) && '' !== $rawData['settings']['_css_classes'] ) {
			self::$currentElementorClass = $rawData['settings']['_css_classes'];
		}
	}
}
