using System;
using System.Collections;
using System.Configuration;
using System.Data;
using System.Linq;
using System.Web;
using System.Web.Security;
using System.Web.UI;
using System.Web.UI.HtmlControls;
using System.Web.UI.WebControls;
using System.Web.UI.WebControls.WebParts;
using System.Xml.Linq;
using Lacuna.RestPki.Api;

namespace WebApplication1
{
    public partial class _Default : System.Web.UI.Page
    {
        protected void Page_Load(object sender, EventArgs e)
        {

            var client = new Lacuna.RestPki.Client.RestPkiClient("https://pki.rest/", "WUHZ_N8j9GnyTx091uDG8qR4Xi1DMmE7_HcBBJw0cE9TN3BRSmJOv7chsOHO4admJvhqmdryz6lM7xjRgl_p5BQhnNEQlXYzt1VWwKkm56h3IJUn8ccdpTCvImy389KKVGPhuxfegvTVBwks7f5NQ9qvr-RlK-z0VSwFKDuRWEqbgS8naXSgIYEDAaENJrB7elQBityEqialfxOjDurYTqHI2VN4rJsrNbPwi9Yqvyilpq9IoyMmR13Vs6AF5SCuAe3ihwAzYlh0vJE7Z-3mt4tTFo8sm4gNq1K0IU8OzCt5sgQVO9zHDcTaT66KkuYcNNTcSJLBd7ezZmEDfNoI0NlRQRvuxq6viSk14tPS-5FmvWj__ffdeqhkdQi6YK0Fkxl5zwrI3YaAMKqi9lMvC7jHLvNaX25rft567LKowNCMxfbb6SoTF9gDaIzOtZBb3lfoXMlJjoh-PS1b2Hsc3a5ef51JwkuGgRaRDiXvh1BUJTVy2Nijwt7aXl7dbqK8l-1_FW48hnzF_micpzeOR4m8ejk");
            var token = client.GetAuthentication().StartWithWebPki(StandardSecurityContexts.PkiBrazil);


        }
    }
}
