using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Builder;
using Microsoft.AspNetCore.Hosting;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Logging;
using CoreWebApp.Classes;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.Formatters;

namespace CoreWebApp {
	public class Startup {
		public Startup(IHostingEnvironment env) {
			var builder = new ConfigurationBuilder()
				 .SetBasePath(env.ContentRootPath)
				 .AddJsonFile("appsettings.json", optional: false, reloadOnChange: true)
				 .AddJsonFile($"appsettings.{env.EnvironmentName}.json", optional: true)
				 .AddEnvironmentVariables();
			Configuration = builder.Build();
		}

		public IConfigurationRoot Configuration { get; }

		// This method gets called by the runtime. Use this method to add services to the container.
		public void ConfigureServices(IServiceCollection services) {

			// Adds services required for using options.
			services.AddOptions();

			// Register the IConfiguration instance which RestPkiOptions binds against.
			services.Configure<RestPkiConfig>(Configuration.GetSection("RestPki"));

			// Add framework services.
			services.AddMvc(options => {
				// Alter the default output formatters so that JsonOutputFormatter is preferred over StringOutputFormatter
				// http://stackoverflow.com/questions/31905390/asp-net-mvc6-actions-returning-strings/42214435#42214435
				var stringFormatter = options.OutputFormatters.OfType<StringOutputFormatter>().FirstOrDefault();
				if (stringFormatter != null) {
					options.OutputFormatters.Remove(stringFormatter);
					options.OutputFormatters.Add(stringFormatter);
				}
			});
		}

		// This method gets called by the runtime. Use this method to configure the HTTP request pipeline.
		public void Configure(IApplicationBuilder app, IHostingEnvironment env, ILoggerFactory loggerFactory) {
			loggerFactory.AddConsole(Configuration.GetSection("Logging"));
			loggerFactory.AddDebug();

			if (env.IsDevelopment()) {
				app.UseDeveloperExceptionPage();
			}
			
			app.UseStaticFiles();

			app.UseMvc(routes => {
				routes.MapRoute(
					 name: "default",
					 template: "{controller=Home}/{action=Index}/{id?}"
				);
				routes.MapSpaFallbackRoute(
					name: "spa-fallback",
					defaults: new { controller = "Home", action = "Index" }
				);
			});
		}
	}
}
