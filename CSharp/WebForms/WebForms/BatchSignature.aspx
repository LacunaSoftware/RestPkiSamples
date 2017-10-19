<%@ Page Language="C#" MasterPagefile="~/Site.Master" AutoEventWireup="true" CodeBehind="BatchSignature.aspx.cs" Inherits="WebForms.BatchSignature" %>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">

	<h2>Batch signature</h2>

	<%--
		UpdatePanel used to refresh only this part of the page. This is needed because if we did a complete postback of the page,
		the Web PKI component would ask for user authorization to sign each document in the batch.
	--%>
	<asp:UpdatePanel runat="server">
		<ContentTemplate>

			<%--
				ListView to show each batch document and either the download link for the signed version (if successful) or an error message (if failed)
			--%>
			<asp:ListView ID="DocumentsListView" runat="server">
				<LayoutTemplate>
					<ul>
						<asp:PlaceHolder ID="itemPlaceholder" runat="server" />
					</ul>
				</LayoutTemplate>
				<ItemTemplate>
					<li>
						Document <asp:Label runat="server" Text='<%# Eval("Id") %>' />
						<asp:Label runat="server" Visible='<%# Eval("Error") != null %>' Text='<%# Eval("Error") %>' CssClass="text-danger" />
						<asp:HyperLink runat="server" Visible='<%# Eval("DownloadLink") != null %>' NavigateUrl='<%# Eval("DownloadLink") %>' Text="download" />
					</li>
				</ItemTemplate>
			</asp:ListView>

			<%--
				Surrounding panel containing the certificate select (combo box) and buttons, which is hidden by the code-behind after the batch starts
			--%>
			<asp:Panel ID="SignatureControlsPanel" runat="server">

				<%-- Render a select (combo box) to list the user's certificates. For now it will be empty, we'll populate it later on (see batch-signature-form.js). --%>
				<div class="form-group">
					<label for="certificateSelect">Choose a certificate</label>
					<select id="certificateSelect" class="form-control"></select>
				</div>

				<%--
					Action buttons. Notice that both buttons have a OnClientClick attribute, which calls the
					client-side javascript functions "sign" and "refresh" below. Both functions return false,
					which prevents the postback.
				--%>
				<asp:Button ID="SignButton" runat="server" class="btn btn-primary" Text="Sign Batch" OnClientClick="return sign();" />
				<asp:Button ID="RefreshButton" runat="server" class="btn btn-default" Text="Refresh" OnClientClick="return refresh();" />

			</asp:Panel>

			<%--
				Hidden fields used to pass data from the code-behind to the javascript and vice-versa 
			--%>
			<asp:HiddenField runat="server" ID="TokenField" />

            <%--
                Hidden fields used by the code-behind to save state between signature steps. These could be alternatively stored on server-side session,
                since we don't need their values on the javascript
            --%>
            <asp:HiddenField runat="server" ID="DocumentIdsField" />
            <asp:HiddenField runat="server" ID="DocumentIndexField" />

			<%--
				Hidden buttons whose click event is fired programmatically by the javascript upon completion of each step in the batch. Notice that
				we cannot use Visible="False" otherwise ASP.NET will omit the button altogether from the rendered page, making it impossible to
				programatically "click" it.
			--%>
			<asp:Button ID="StartBatchButton" runat="server" OnClick="StartBatchButton_Click" Style="display: none;" />
			<asp:Button ID="CompleteSignatureAndStartNextButton" runat="server" OnClick="CompleteSignatureAndStartNextButton_Click" Style="display: none;" />

		</ContentTemplate>
	</asp:UpdatePanel>

    <%--
		Include the "webpki-batch" bundle, which includes the following javascript files (see App_Start\BundleConfig.cs):
		- jquery.blockUI.js       : jQuery plugin to block the UI
		- lacuna-web-pki-2.6.1.js : Javascript library to access the Web PKI component (client-side component used to access the user's certificates)
		- batch-signature-form.js : Javascript code to call the Web PKI component and to do the batch signature (merely a sample, you are encouraged to adapt it)
	--%>
	<asp:PlaceHolder runat="server">
        <%: Scripts.Render("~/bundles/webpki-batch") %>
    </asp:PlaceHolder>

	<script>
		
		<%--
			Set the number of documents in the batch on the "batch signature form" javascript module. This is needed in order to request
			user permissions to make N signatures (the Web PKI component requires us to inform the number of signatures that will be performed
			on the batch).
		--%>
		batchSignatureForm.setDocumentCount(<%= DocumentIds.Count %>);

		<%--
			The function below is called by ASP.NET's javascripts when the page is loaded and also when the UpdatePanel above changes.
			We'll call the pageLoaded() function on the "batch signature form" javascript module passing references to our page's elements and
			hidden fields
		--%>
		function pageLoad() {

			batchSignatureForm.pageLoad({

				<%-- Reference to the certificate combo box --%>
				certificateSelect: $('#certificateSelect'),

				<%-- Hidden buttons to transfer the execution back to the code-behind --%>
				startBatchButton: $('#<%= StartBatchButton.ClientID %>'),
                completeSignatureAndStartNextButton: $('#<%= CompleteSignatureAndStartNextButton.ClientID %>'),

				<%-- Hidden fields to pass data to and from the code-behind --%>
				tokenField: $('#<%= TokenField.ClientID %>')

			});
		}

		<%-- Client-side function called when the user clicks the "Sign" button --%>
		function sign() {
			batchSignatureForm.start();
			return false; // prevent postback
		}

		<%-- Client-side function called when the user clicks the "Refresh" button --%>
		function refresh() {
			batchSignatureForm.refresh();
			return false; // prevent postback
		}

	</script>

</asp:Content>
