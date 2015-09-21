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
			var restPkiUrl = "https://restpkibeta.azurewebsites.net/";
			//var restPkiUrl = "http://localhost:53358/";
			var restPkiAccessToken = "55bemqp-bpmsWbR0OY-lj-RdRSV0bvOgV97LHY2N_Z7kEZK5BzDYGtkYMT61GcqSyie0_GcAIGq7AzmPkiMpvgo7XoCNIRmdjT45iY4eA3ugQgeyLtGbGYENvI-9eF5xPfIgxj9_SMbtjzISIE8kedDoMey96f7v1p5FpwSrIFfkAsffMcuzTKtXsDGLg-4j4ADFPHagj9t9Aqgfhu-eabWTHnwhusLtvlEi3dvpctkOSF_GnrMwk2_Ki7V8v6N5q9b0I8UDGPuuklLWe_1X6LGTKpo2vwngNNHaTJTnCDpviHce17JIxKlVl-RvHk27Ewkm-NEiPUpYBiguv5rDpE-NJPmZ8_wvIMghYeaEc9ea9Sj6qdAOaaSeH9WC2RDmb1iD-RPHvQf5Qak3z835Y6M48BW4Munqss6yi4Hha5X_Fqocy7MAZgACT8wmL-spgXCcZL0N1_IDJgd9pr9aykfzIzvP2on-azKei6cbNF6UDsb2m1xL0gKgvA0r_HK5ULhRAA";
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
