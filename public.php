<?php
	set_include_path(__DIR__ ."/include" . PATH_SEPARATOR .
		get_include_path());

	require_once "autoload.php";
	require_once "sessions.php";

	Config::sanity_check();

	startup_gettext();

	if (!init_plugins()) return;

	$span = OpenTelemetry\API\Trace\Span::getCurrent();

	$method = (string)clean($_REQUEST["op"]);

	// shortcut syntax for public (exposed) methods (?op=plugin--pmethod&...params)
	if (strpos($method, PluginHost::PUBLIC_METHOD_DELIMITER) !== false) {
		list ($plugin, $pmethod) = explode(PluginHost::PUBLIC_METHOD_DELIMITER, $method, 2);

		// TODO: better implementation that won't modify $_REQUEST
		$_REQUEST["plugin"] = $plugin;
		$_REQUEST["pmethod"] = $pmethod;

		$method = "pluginhandler";
	}

	$override = PluginHost::getInstance()->lookup_handler("public", $method);

	if ($override) {
		$handler = $override;
	} else {
		$handler = new Handler_Public($_REQUEST);
	}

	if (strpos($method, "_") === 0) {
		user_error("Refusing to invoke method $method which starts with underscore.", E_USER_WARNING);
		header("Content-Type: text/json");
		print Errors::to_json(Errors::E_UNAUTHORIZED);
		$span->setAttribute('error', Errors::E_UNAUTHORIZED);
		return;
	}

	if (implements_interface($handler, "IHandler") && $handler->before($method)) {
		$span->addEvent("construct/$method");
		if ($method && method_exists($handler, $method)) {
			$reflection = new ReflectionMethod($handler, $method);

			if ($reflection->getNumberOfRequiredParameters() == 0) {
				$span->addEvent("invoke/$method");
				$handler->$method();
			} else {
				user_error("Refusing to invoke method $method which has required parameters.", E_USER_WARNING);
				header("Content-Type: text/json");
				print Errors::to_json(Errors::E_UNAUTHORIZED);
				$span->setAttribute('error', Errors::E_UNAUTHORIZED);
			}
		} else if (method_exists($handler, 'index')) {
			$span->addEvent("index");
			$handler->index();
		}
		$span->addEvent("after/$method");
		$handler->after();
		return;
	}

	header("Content-Type: text/plain");
	print Errors::to_json(Errors::E_UNKNOWN_METHOD);
	$span->setAttribute('error', Errors::E_UNKNOWN_METHOD);

