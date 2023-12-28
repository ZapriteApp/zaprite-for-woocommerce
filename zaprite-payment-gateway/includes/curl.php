<?php
namespace ZapritePlugin;

class CurlWrapper {
	private function request( $method, $url, $params, $headers, $data ) {
		error_log( "ZAPRITE: curl $url" );
		$url = add_query_arg( $params, $url );
		$r   = wp_remote_request(
			$url,
			array(
				'method'  => $method,
				'headers' => $headers,
				'body'    => $data,
			)
		);

		if ( is_wp_error( $r ) ) {
			error_log( 'WP_Error: ' . $r->get_error_message() );
			return array(
				'status'   => 500,
				'response' => $r->get_error_message(),
			);
		}

		return array(
			'status'   => $r['response']['code'],
			'response' => json_decode( $r['body'], true ),
		);
	}

	public function get( $url, $params, $headers ) {
		return $this->request( 'GET', $url, $params, $headers, null );
	}

	public function post( $url, $params, $data, $headers ) {
		return $this->request( 'POST', $url, $params, $headers, $data );
	}
}
