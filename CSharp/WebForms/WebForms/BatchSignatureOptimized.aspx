<%@ Page Language="C#" MasterPagefile="~/Site.Master" AutoEventWireup="true" CodeBehind="BatchSignatureOptimized.aspx.cs" Inherits="WebForms.BatchSignatureOptimized" %>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">

    <%-- Panel that shows the error messages. It will be filled programatically on javascript --%>
    <div id="messagesPanel"></div>

    <h2>Batch signature optimized</h2>

    <%-- Surrounding panel containing the certificate select (combo box) and buttons, which is hidden by the code-behind after --%>
	<asp:Panel ID="SignatureControlsPanel" runat="server">

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

	</asp:Panel>

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
			The function below is called by ASP.NET's javascripts when the page is loaded and also when the UpdatePanel above changes.
			We'll call the pageLoaded() function on the "batch signature optimized form" javascript module passing references to our 
            page's elements and hidden fields
		--%>
		function pageLoad() {

            batchSignatureOptimizedForm.pageLoad({
				<%-- Reference to the certificate combo box --%>
				certificateSelect: $('#certificateSelect'),
				<%-- Ids of documents --%>
                documentsIds: [<%= string.Join(",", DocumentsIds) %>]
			});
		}

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