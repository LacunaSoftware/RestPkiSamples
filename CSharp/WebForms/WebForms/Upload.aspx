<%@ Page Language="C#" MasterPageFile="~/Site.Master" AutoEventWireup="true" CodeBehind="Upload.aspx.cs" Inherits="WebForms.Upload" %>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">

    <h2>Upload a file</h2>

    <div class="form-group">
        <label>Select file:</label>
        <asp:fileupload id="userfile" runat="server" class="form-control"></asp:fileupload>
    </div>
    <asp:button runat="server" class="btn btn-primary" Text="Upload" OnClick="UploadButton_Click" />

</asp:Content>