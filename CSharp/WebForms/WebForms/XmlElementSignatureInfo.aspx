<%@ Page Language="C#" MasterPageFile="~/Site.Master" AutoEventWireup="true" CodeBehind="XmlElementSignatureInfo.aspx.cs" Inherits="WebForms.XmlElementSignatureInfo" %>
<%@ PreviousPageType VirtualPath="~/XmlElementSignature.aspx" %>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">

	<h2>XML Element Signature</h2>

	<p>File signed successfully! <a href="Download?fileId=<%= signatureFilename %>">Click here to download the signed file</a></p>

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
</asp:Content>
