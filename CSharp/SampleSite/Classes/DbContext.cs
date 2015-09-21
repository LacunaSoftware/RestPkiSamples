using System;
using System.Collections.Generic;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;
using System.Data.Entity;
using System.Linq;
using System.Web;

namespace SampleSite.Classes {

	// This is our DbContext class. It implements the IPkiNonceStoreContext interface, which
	// is needed in order to use the Entity Framework Connector to store cryptographic nonces
	// used in certificate authentication. For more information, see class AuthenticationController
	// on the Api folder or visit:
	// http://pki.lacunasoftware.com/Help/html/0195da17-db87-4d4f-8ce2-c21b140c10c3.htm#NonceStore
	public class DbContext : System.Data.Entity.DbContext {

      public DbContext(): base("DefaultConnection") {

      }

		// Signatures successfuly performed
		public DbSet<Signature> Signatures { get; set; }
	}

	public class Signature {
		
		[Key]
		public Guid Id { get; set; }
		
		public SignatureTypes Type { get; set; }
		public byte[] Content { get; set; }
		public bool IsCompressed { get; set; }

		public static Signature Create() {
			return new Signature() {
				Id = Guid.NewGuid()
			};
		}
	}

	public enum SignatureTypes {
		Cades = 1,
		Pades = 2
	}
}
