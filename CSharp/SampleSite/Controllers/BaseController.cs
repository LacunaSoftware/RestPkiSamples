using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {
	public class BaseController : Controller {

		protected void SetExpiredPage() {
			Response.Cache.SetCacheability(HttpCacheability.NoCache);
			Response.Cache.SetNoStore();
			Response.Expires = -1;
			Response.AddHeader("Last-Modified", DateTimeOffset.UtcNow.ToString("R"));
		}

	}
}
