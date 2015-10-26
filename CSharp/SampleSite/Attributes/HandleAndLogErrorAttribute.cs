using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.Http.Filters;

namespace SampleSite {
	public class HandleAndLogErrorAttribute : ExceptionFilterAttribute {

		private static NLog.Logger logger = NLog.LogManager.GetCurrentClassLogger();

		public override void OnException(HttpActionExecutedContext actionExecutedContext) {
			var exception = actionExecutedContext.Exception;
			logger.Error(exception, "Unhandled exception in Web API");
		}
	}
}
