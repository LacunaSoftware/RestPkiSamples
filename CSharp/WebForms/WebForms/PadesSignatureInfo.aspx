<%@ Page Language="C#" MasterPageFile="~/Site.Master" AutoEventWireup="true" CodeBehind="PadesSignatureInfo.aspx.cs" Inherits="WebForms.PadesSignatureInfo" %>

<%@ PreviousPageType VirtualPath="~/PadesSignature.aspx" %>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">

	<h2>PAdES Signature</h2>

	<p>File signed successfully! <a href="Download?file=<%= signatureFilename.Replace(".", "_") %>">Click here to download the signed file</a></p>

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
			</ul>
		</li>
	</ul>
</asp:Content>
