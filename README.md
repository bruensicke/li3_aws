# li3_aws

lithium plugin for interacting with amazon web services.

Currently only S3 is supported, but more parts of the amazon webservices will follow.

## Installation

Add a submodule to your li3 libraries:

	git submodule add git@github.com:bruensicke/li3_aws.git libraries/li3_aws

and activate it in you app (config/bootstrap/libraries.php), of course:

	Libraries::add('li3_aws');

## Configuration

	Connections::add('s3', array(
		'key' => 'KAIS5N6CERYXYBIPBWUQ',
		'secret' => 'fcrx47urgUBmlGHl7rxjxr8b+I2s/r8V7TTH594l'
	));

## Usage

	$buckets = S3::buckets();

	$assets = S3::bucket($name);

## Credits

* [li3](http://www.lithify.me)
