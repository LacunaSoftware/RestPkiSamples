<%@ Page Language="C#" MasterPageFile="~/Site.Master" AutoEventWireup="true" CodeBehind="PadesSignatureInfo.aspx.cs" Inherits="WebForms.PadesSignatureInfo" %>

<%@ PreviousPageType VirtualPath="~/PadesSignature.aspx" %>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">

	<h2>PAdES Signature</h2>

	<p>File signed successfully!

	<p>Signer information:</p>
	<ul>
		<li>Subject: <%= signerCertificate.SubjectName.CommonName %></li>
		<li>Email: <%= signerCertificate.EmailAddress %></li>
		<li>ICP-Brasil fields
			<ul>
				<li>Tipo de certificado: <%= signerCertificate.PkiBrazil.CertificateType %></li>
				<li>CPF: <%= signerCertificate.PkiBrazil.Cpf %></li>
				<li>Responsavel: <%= signerCertificate.PkiBrazil.Responsavel %></li>
				<li>Empresa: <%= signerCertificate.PkiBrazil.CompanyName %></li>
				<li>CNPJ: <%= signerCertificate.PkiBrazil.Cnpj %></li>
                <li>RG: <%= signerCertificate.PkiBrazil.RGNumero %> <%= signerCertificate.PkiBrazil.RGEmissor %> <%= signerCertificate.PkiBrazil.RGEmissorUF %></li>
                <li>OAB: <%= signerCertificate.PkiBrazil.OabNumero %> <%= signerCertificate.PkiBrazil.OabUF %></li>
			</ul>
		</li>
	</ul>

    <h3>Actions:</h3>
    <ul>
        <li><a href='Download?file=<%= signatureFilename %>'>Download the signed file</a></li>
		<li><a href='PrinterFriendlyVersion?file=<%= signatureFilename %>'>Download a printer-friendly version of the signed file</a></li>
        <%--<li><a href='OpenPadesSignature?userfile=<%= signatureFilename.Replace(".", "_") %>'>Open/validate the signed file</a></li>--%>
        <li><a href='PadesSignature?userfile=<%= signatureFilename %>'>Co-sign with another certificate</a></li>
    </ul>
</asp:Content>
