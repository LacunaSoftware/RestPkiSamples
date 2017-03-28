<%@ Page Language="C#" MasterPageFile="~/Site.Master" AutoEventWireup="true" CodeBehind="CadesSignature.aspx.cs" Inherits="WebForms.CadesSignature" %>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">
	
	<h2>CAdES Signature</h2>

    <label>File to sign</label>
    <%if (!String.IsNullOrEmpty(UserFile)) { %>
        <p>You'll be signing <a href='/Download?file=<%= UserFile %>'>this document</a>.</p>
    <% } else if (!String.IsNullOrEmpty(CmsFile)) { %>
        <p>You'll be signing <a href='/Download?file=<%= CmsFile %>'>this CMS</a>.</p>
    <% } else { %>
        <p>You'll be signing <a href='/Download?file=SampleDocument_pdf&from=content'>this sample document</a>.</p>
    <% } %>
	
	<%-- Render a select (combo box) to list the user's certificates. For now it will be empty, we'll populate it later on (see signature-form.js). --%>
	<div class="form-group">
		<label for="certificateSelect">Choose a certificate</label>
		<select id="certificateSelect" class="form-control"></select>
	</div>
	
	<%--
		Action buttons. Notice that both buttons have a OnClientClick attribute, which calls the
		client-side javascript functions "sign" and "refresh" below. Both functions return false,
		which prevents the postback.
	--%>
	<asp:Button ID="SignButton" runat="server" class="btn btn-primary" Text="Sign File" OnClientClick="return sign();" />
	<asp:Button ID="RefreshButton" runat="server" class="btn btn-default" Text="Refresh" OnClientClick="return refresh();" />

	<%--
		Hidden button whose click event is fired by the "signature form" javascript upon completion
		of the signature process. Notice that we cannot use Visible="False" otherwise ASP.NET will 
		omit the button altogether from the rendered page, making it impossible to programatically
		"click" it.
	--%>
	<asp:Button ID="SubmitButton" runat="server" OnClick="SubmitButton_Click" style="display: none;"  />

	<%--
		Include the "webpki" bundle, which includes the following javascript files (see App_Start\BundleConfig.cs):
		- jquery.blockUI.js       : jQuery plugin to block the UI
		- lacuna-web-pki-2.5.0.js : Javascript library to access the Web PKI component (client-side component used to access the user's certificates)
		- signature-form.js       : Javascript code to call the Web PKI component
	--%>
	<asp:PlaceHolder runat="server">
        <%: Scripts.Render("~/bundles/webpki") %>
    </asp:PlaceHolder>

	<script>
		<%--
			Once the page is loaded, we'll call the init() function on the signature-form.js file passing the token
			acquired from Rest PKI and references to the certificates select element and the button that should be
			triggered once the operation is completed.
		--%>
		$(function () {
			signatureForm.init({
				token: '<%= ViewState["Token"] %>',
				certificateSelect: $('#certificateSelect'),
				submitButton: $('#<%= SubmitButton.ClientID %>')
			});
		});
		<%-- Client-side function called when the user clicks the "Sign File" button --%>
		function sign() {
			signatureForm.sign();
			return false; // prevent postback
		}
		<%-- Client-side function called when the user clicks the "Refresh" button --%>
		function refresh() {
			signatureForm.refresh();
			return false; // prevent postback
		}
	</script>
</asp:Content>
