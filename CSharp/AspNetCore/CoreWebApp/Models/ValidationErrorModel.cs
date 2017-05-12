using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace CoreWebApp.Models {

	public class ValidationErrorModel {

		public ValidationResultsModel ValidationResults { get; set; }

		public ValidationErrorModel(ValidationResults vr) {
			ValidationResults = new ValidationResultsModel(vr);
		}
	}

	public class ValidationResultsModel {

		public List<ValidationItemModel> PassedChecks { get; set; }
		public List<ValidationItemModel> Errors { get; set; }
		public List<ValidationItemModel> Warnings { get; set; }
        public bool IsValid { get; set; }

		public ValidationResultsModel(ValidationResults vr) {
			PassedChecks = vr.PassedChecks.Select(i => new ValidationItemModel(i)).ToList();
			Warnings = vr.Warnings.Select(i => new ValidationItemModel(i)).ToList();
			Errors = vr.Errors.Select(i => new ValidationItemModel(i)).ToList();
            IsValid = Errors.Count == 0;
		}
	}

	public class ValidationItemModel {

		public string Type { get; set; }

		public string Message { get; set; }

		public string Detail { get; set; }

		public ValidationResultsModel InnerValidationResults { get; set; }

		public ValidationItemModel(ValidationItem vi) {
			Type = vi.Type.ToString();
			Message = vi.Message;
			Detail = vi.Detail;
			if (vi.InnerValidationResults != null) {
				InnerValidationResults = new ValidationResultsModel(vi.InnerValidationResults);
			}
		}
	}
}
