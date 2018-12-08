<?php

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

$steps->Then(
	'/^the return code should( not)? be (\d+)$/',
	function ( $world, $not, $return_code ) {
		if ( ( ! $not && $return_code != $world->result->return_code ) || ( $not && $return_code == $world->result->return_code ) ) {
			throw new RuntimeException( $world->result );
		}
	}
);

$steps->Then(
	'/^(STDOUT|STDERR) should (be|contain|not contain):$/',
	function ( $world, $stream, $action, PyStringNode $expected ) {

		$stream = strtolower( $stream );

		$expected = $world->replace_variables( (string) $expected );

		checkString( $world->result->$stream, $expected, $action, $world->result );
	}
);

$steps->Then(
	'/^(STDOUT|STDERR) should be a number$/',
	function ( $world, $stream ) {

		$stream = strtolower( $stream );

		assertNumeric( trim( $world->result->$stream, "\n" ) );
	}
);

$steps->Then(
	'/^(STDOUT|STDERR) should not be a number$/',
	function ( $world, $stream ) {

		$stream = strtolower( $stream );

		assertNotNumeric( trim( $world->result->$stream, "\n" ) );
	}
);

$steps->Then(
	'/^STDOUT should be a table containing rows:$/',
	function ( $world, TableNode $expected ) {
		$output      = $world->result->stdout;
		$actual_rows = explode( "\n", rtrim( $output, "\n" ) );

		$expected_rows = array();
		foreach ( $expected->getRows() as $row ) {
			$expected_rows[] = $world->replace_variables( implode( "\t", $row ) );
		}

		compareTables( $expected_rows, $actual_rows, $output );
	}
);

$steps->Then(
	'/^STDOUT should end with a table containing rows:$/',
	function ( $world, TableNode $expected ) {
		$output      = $world->result->stdout;
		$actual_rows = explode( "\n", rtrim( $output, "\n" ) );

		$expected_rows = array();
		foreach ( $expected->getRows() as $row ) {
			$expected_rows[] = $world->replace_variables( implode( "\t", $row ) );
		}

		$start = array_search( $expected_rows[0], $actual_rows, true );

		if ( false === $start ) {
			throw new \Exception( $world->result );
		}

		compareTables( $expected_rows, array_slice( $actual_rows, $start ), $output );
	}
);

$steps->Then(
	'/^STDOUT should be JSON containing:$/',
	function ( $world, PyStringNode $expected ) {
		$output   = $world->result->stdout;
		$expected = $world->replace_variables( (string) $expected );

		if ( ! checkThatJsonStringContainsJsonString( $output, $expected ) ) {
			throw new \Exception( $world->result );
		}
	}
);

$steps->Then(
	'/^STDOUT should be a JSON array containing:$/',
	function ( $world, PyStringNode $expected ) {
		$output   = $world->result->stdout;
		$expected = $world->replace_variables( (string) $expected );

		$actualValues   = json_decode( $output );
		$expectedValues = json_decode( $expected );

		$missing = array_diff( $expectedValues, $actualValues );
		if ( ! empty( $missing ) ) {
			throw new \Exception( $world->result );
		}
	}
);

$steps->Then(
	'/^STDOUT should be CSV containing:$/',
	function ( $world, TableNode $expected ) {
		$output = $world->result->stdout;

		$expected_rows = $expected->getRows();
		foreach ( $expected as &$row ) {
			foreach ( $row as &$value ) {
				$value = $world->replace_variables( $value );
			}
		}

		if ( ! checkThatCsvStringContainsValues( $output, $expected_rows ) ) {
			throw new \Exception( $world->result );
		}
	}
);

$steps->Then(
	'/^STDOUT should be YAML containing:$/',
	function ( $world, PyStringNode $expected ) {
		$output   = $world->result->stdout;
		$expected = $world->replace_variables( (string) $expected );

		if ( ! checkThatYamlStringContainsYamlString( $output, $expected ) ) {
			throw new \Exception( $world->result );
		}
	}
);

$steps->Then(
	'/^(STDOUT|STDERR) should be empty$/',
	function ( $world, $stream ) {

		$stream = strtolower( $stream );

		if ( ! empty( $world->result->$stream ) ) {
			throw new \Exception( $world->result );
		}
	}
);

$steps->Then(
	'/^(STDOUT|STDERR) should not be empty$/',
	function ( $world, $stream ) {

		$stream = strtolower( $stream );

		if ( '' === rtrim( $world->result->$stream, "\n" ) ) {
			throw new Exception( $world->result );
		}
	}
);

$steps->Then(
	'/^(STDOUT|STDERR) should be a version string (<|<=|>|>=|==|=|!=|<>) ([+\w.{}-]+)$/',
	function ( $world, $stream, $operator, $goal_ver ) {
		$goal_ver = $world->replace_variables( $goal_ver );
		$stream   = strtolower( $stream );
		if ( false === version_compare( trim( $world->result->$stream, "\n" ), $goal_ver, $operator ) ) {
			throw new Exception( $world->result );
		}
	}
);

$steps->Then(
	'/^the (.+) (file|directory) should (exist|not exist|be:|contain:|not contain:)$/',
	function ( $world, $path, $type, $action, $expected = null ) {
		$path = $world->replace_variables( $path );

		// If it's a relative path, make it relative to the current test dir
		if ( '/' !== $path[0] ) {
			$path = $world->variables['RUN_DIR'] . "/$path";
		}

		if ( 'file' === $type ) {
			$test = 'file_exists';
		} elseif ( 'directory' === $type ) {
			$test = 'is_dir';
		}

		switch ( $action ) {
			case 'exist':
				if ( ! $test( $path ) ) {
					throw new Exception( "$path doesn't exist." );
				}
				break;
			case 'not exist':
				if ( $test( $path ) ) {
					throw new Exception( "$path exists." );
				}
				break;
			default:
				if ( ! $test( $path ) ) {
					throw new Exception( "$path doesn't exist." );
				}
				$action   = substr( $action, 0, -1 );
				$expected = $world->replace_variables( (string) $expected );
				if ( 'file' === $type ) {
					$contents = file_get_contents( $path );
				} elseif ( 'directory' === $type ) {
					$files = glob( rtrim( $path, '/' ) . '/*' );
					foreach ( $files as &$file ) {
						$file = str_replace( $path . '/', '', $file );
					}
					$contents = implode( PHP_EOL, $files );
				}
				checkString( $contents, $expected, $action );
		}
	}
);

$steps->Then(
	'/^the contents of the (.+) file should match (((\/.+\/)|(#.+#))([a-z]+)?)$/',
	function ( $world, $path, $expected ) {
		$path = $world->replace_variables( $path );
		// If it's a relative path, make it relative to the current test dir
		if ( '/' !== $path[0] ) {
			$path = $world->variables['RUN_DIR'] . "/$path";
		}
		$contents = file_get_contents( $path );
		assertRegExp( $expected, $contents );
	}
);

$steps->Then(
	'/^(STDOUT|STDERR) should match (((\/.+\/)|(#.+#))([a-z]+)?)$/',
	function ( $world, $stream, $expected ) {
		$stream = strtolower( $stream );
		assertRegExp( $expected, $world->result->$stream );
	}
);

$steps->Then(
	'/^an email should (be sent|not be sent)$/', function( $world, $expected ) {
		if ( 'be sent' === $expected ) {
			assertNotEquals( 0, $world->email_sends );
		} elseif ( 'not be sent' === $expected ) {
			assertEquals( 0, $world->email_sends );
		} else {
			throw new Exception( 'Invalid expectation' );
		}
	}
);

$steps->Then(
	'/^the HTTP status code should be (\d+)$/',
	function ( $world, $return_code ) {
		$response = \Requests::request( 'http://localhost:8080' );
		assertEquals( $return_code, $response->status_code );
	}
);
