<%@ Page Language="C#" MasterPagefile="~/Site.Master" AutoEventWireup="true" CodeBehind="BatchSignatureOptimized.aspx.cs" Inherits="WebForms.BatchSignatureOptimized" %>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">

    <%-- Panel that shows the error messages. It will be filled programatically on javascript --%>
    <div id="messagesPanel"></div>

    <h2>Batch signature optimized</h2>

    <div class="form-group">
        <label>File to sign</label>
		<p>
			You'll be signing the following files:
			<%-- UL element to hold the batch's documents (we'll render these programatically, see batch-signature-optimized-form.js) --%>
			<ul id="docList" />
		</p>
    </div>

	<%--
        Render a select (combo box) to list the user's certificates. For now it will be empty, we'll populate it later 
        on (see batch-signature-optimized-form.js). 
    --%>
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

    <%--
		Include the "webpki-batch-optimized" bundle, which includes the following javascript files (see App_Start\BundleConfig.cs):
		- jquery.blockUI.js                 : jQuery plugin to block the UI
		- lacuna-web-pki-2.6.1.js           : Javascript library to access the Web PKI component (client-side component used to access the user's certificates)
		- batch-signature-optimized-form.js : Javascript code to call the Web PKI component and to do the batch signature (merely a sample, you are encouraged to adapt it)
	--%>
	<asp:PlaceHolder runat="server">
        <%: Scripts.Render("~/bundles/webpki-batch-optimized") %>
    </asp:PlaceHolder>

    <script>

		<%--
			Once the page is loaded, we'll call the init() function on the batch-signature-optimized-form.js file passing the list of
			Ids that need signing (we'll iterate over the list on the Javascript code) and a reference to the certificates select element.
		--%>
		$(function () {
			batchSignatureOptimizedForm.init({
				<%-- Reference to the certificate combo box --%>
				certificateSelect: $('#certificateSelect'),
				<%-- Ids of documents. DocumentsIds is a protected property on the page-behind, filled on the Page_Load method. --%>
                documentsIds: [<%= string.Join(",", DocumentsIds) %>],
                <%-- Sign button's reference to be disable when the batch signature finishes --%>
                signButton: $('#<%= SignButton.ClientID %>')
			});
		});

		<%-- Client-side function called when the user clicks the "Sign" button --%>
		function sign() {
            batchSignatureOptimizedForm.sign();
			return false; // prevent postback
		}

		<%-- Client-side function called when the user clicks the "Refresh" button --%>
		function refresh() {
            batchSignatureOptimizedForm.refresh();
			return false; // prevent postback
		}

	</script>

</asp:Content>