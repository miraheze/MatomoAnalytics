<?php

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
namespace Miraheze\MatomoAnalytics;

/**
 * A class containing constants representing the names of configuration variables,
 * to protect against typos.
 */
class ConfigNames {

	public const ServerURL = 'MatomoAnalyticsServerURL';

	public const SiteID = 'MatomoAnalyticsSiteID';

	public const GlobalID = 'MatomoAnalyticsGlobalID';

	public const TokenAuth = 'MatomoAnalyticsTokenAuth';

	public const UseDB = 'MatomoAnalyticsUseDB';

	public const DisableJS = 'MatomoAnalyticsDisableJS';

	public const DisableCookie = 'MatomoAnalyticsDisableCookie';

	public const ForceGetRequest = 'MatomoAnalyticsForceGetRequest';
}
