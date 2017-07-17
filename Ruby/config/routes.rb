Rails.application.routes.draw do

    root :to => 'home#index'

    get 'upload', to: 'upload#index'
    post 'upload', to: 'upload#action'

    get 'authentication', to: 'authentication#index'
    post 'authentication', to: 'authentication#action'

    get 'pades_signature', to: 'pades_signature#index'
    post 'pades_signature', to: 'pades_signature#action'

    get 'full_xml_signature', to: 'full_xml_signature#index'
    post 'full_xml_signature', to: 'full_xml_signature#action'

    get 'xml_element_signature', to: 'xml_element_signature#index'
    post 'xml_element_signature', to: 'xml_element_signature#action'

end
