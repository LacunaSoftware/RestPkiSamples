using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace CoreWebApp.Classes {

	public static class Util {

		public static RestPkiClient GetRestPkiClient(RestPkiConfig config) {
			return new RestPkiClient(!string.IsNullOrEmpty(config.Endpoint) ? config.Endpoint : "https://pki.rest/", config.AccessToken);
		}

	}
}
