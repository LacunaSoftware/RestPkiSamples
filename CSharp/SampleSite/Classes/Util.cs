using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;

namespace SampleSite.Classes {

	public class Util {

		public static string ContentPath {
			get {
				return HttpContext.Current.Server.MapPath("~/Content");
			}
		}

		public static byte[] GetPdfStampContent() {
			return File.ReadAllBytes(Path.Combine(ContentPath, "PdfStamp.png"));
		}

		public static byte[] GetSampleDocContent() {
         return File.ReadAllBytes(Path.Combine(ContentPath, "SampleDocument.pdf"));
      }

		public static RestPkiClient GetRestPkiClient() {
			var restPkiUrl = "https://restpki.lacunasoftware.com/";
			//var restPkiUrl = "http://localhost:53358/";
			var restPkiAccessToken = "lie0i89rmua-cXwA7KKS1P8XBDmZSPPkr93b2arD-PlO4nPTsruYGGJ395vsL0WDZQLqdYHHcmi5QCqI2C-dfsHSO5W-yCs6H-ui5TDDE445qLwg7K4bMq5-rY8B5c0_yzFmEgEEdbbT41sj_ryPqpcbhygWvz86J1-bUZrV4FbRt9Ew64rFdQnhSuMUTHavSUGrQjjJL57L5FOzmTo5SNTHqBtOAkP9zK0s1FxsIXZb_04z_u-snd25xE1PFOeF5nwWbipowe-w44jGUY9lZ9_OzHpTikMvdZA-r3c4WwIz4srwsaK4HphuNmPgaHKrnDdjfICV1uuvt7pwqRNiiWbw6uZL_8AA8jntgPjCvORnKL37hlD_g7nVtADHZlD8EuvBOFk1U9vdqEVU6D6yPOLxVt-4RLThePAS0i8vrO-eRzeCkPghKjD1KGVIBerwXpH9cbHfc7B0NgLq81KehCGhd3JhRmycsulha47W5Q6QSqkS2cn7Mm9A_4WWsuTikULQLQ";
			return new RestPkiClient(restPkiUrl, restPkiAccessToken);
      }

		public static Guid SecurityContextId {
			get {
				//return new Guid("53453fe6-66eb-4a36-89d6-a88daa3f9700");
				return Lacuna.RestPki.Api.StandardSecurityContexts.PkiBrazil;
			}
		}

	}
}
