require 'test_helper'

class FullXmlSignatureControllerTest < ActionController::TestCase
  test "should get index" do
    get :index
    assert_response :success
  end

  test "should get action" do
    get :action
    assert_response :success
  end

end
