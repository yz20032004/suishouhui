<?php
$config = array (
		//签名方式,默认为RSA2(RSA2048)
		'sign_type' => "RSA2",

		//支付宝公钥
		'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArwDzteF4vnSnuvaGkbO2etzZcbQi/0wvc6rxuN5Kkbgql65fBYfWitKHVelAEvJjeRsxLTAiJfE1D7cZwa7Gf+oJoDdW0701f8dALlar8NfvMm5PLirDupke84VYB41MVWx5ODiAMRng/VEl1sWZ8+TzOA2rq17h5TOT86Xh6T+JsF/hc+t4kc8qW87AA9sO2AamMGzzqib1H6d7TiDorCnPd0cLfcGQ15lypaDKTgLnbeV13uNddEmxYAqyM559AwGC2a8qFD2QzWsZSpiQwN28DXd+0xWrI/QifgnmRi3aorAdWFEI7bBnKeOOWTPSf+jTzuu2vj6X7LWWjKBTCwIDAQAB",

		//商户私钥
		'merchant_private_key' => "MIIEpQIBAAKCAQEAxBS3mbza7FIwJeOQbdR4qAzu8oFFW3sQRRGx1Q6oWK2pabH/HMSQrC8gwhFXSOgYnhbeqTVcllyUKiWbLVoIinxSxVbActrexaWyDSJxfE9S2ATMAOAJQvBgWktMxvtjBiwFBUPc0GGFbH0dWkWlJZmYxybzm3ogtlBVSjq6BB4kK/fhbpiFoPiF6O3C+sCbwryuuhJTtyvqzRr07UBLmu/jCjRSLkiBeLf11any5AfGiSj/91MT6nxKklOhm2yqpFHv58qd/lfpda7UmRMhjvxATxxFPkdmKBR6zSTVQBeLS7xWEgNdwruCwRs48FTlCER6t6J3nQMHvzBu77xqSQIDAQABAoIBAQCnIZWBWCUua7uYgAiaZRFXBHcjgyZQHGw4wyVWGkFKHPQbIFn39l/uM9lzMX65qQNatNmjVtWNYGn2JsnG9Uf0apXOmOw+uepKg7ppUaNzttVBRY1xbYTXZrZqIGbX8GxuVVUOMNLlV0p3t4kuadCjZPGBRuU77/Q0EfMw6y+k02HUendTZNspewcDKbRv2tER7kYPTMd2svt/oocaR1ou1z8wOX2t0C7adwtOJMSpxXRq+f5SJGf3D18nPfZiGOJxAZ2znChlyUZimeKSVARI3x9tvbFLJiDdGuPNHmsCXo9ueY1ISxcVWw2koSsztqF++u7lFPKcv0Hp/X5dqkhVAoGBAOsi+FTtYjBYSDUSDM0mGJGoyVb4+WcIgEDiie7fn3v1dsI8+t+2/5MfWkAoICQW5FBP3+9b9hp3RGB2cFmcD7jfja2LtSn8fzwhQIz9K2888kro8I6lbEuniY2ow3zdK2aihQEt0T9VThtbWV7DQTTo9ejpgXQ0Fgw+9+XTgXhDAoGBANV6nR7LBaSUInLAUaO2pHhEv270Ux45jOYB/AiHYcuistbYx3R1ROLOfmsPT+VT8hvVzM4sh4HFyQX+E356RJ7552t5v7opOS0naHfKp/hJYU9ejIsNURVKV847JrUru9Pe3yaBRQjyIFuZJSOz///RrBiSVnyj4lMKWpWNRqCDAoGBAJBGQME7dofbY1LM5HXr7h9RxVhMJBuIJ8moNqIPLDhPALNl3zjtfBu1nRbIiBAcy7JLkEe50WOj0pytj3osO6lf4fqeQmakux0V+hmILeJvLuuvygp8jdpNziTRbEFtI3gyOBHlHwMRQH+gqVFv1M62MkzbGcdHPk/9QvYNtN8pAoGAdLtDRx9EyqWl4JGBEsBAVvNgqk83VAOsI+lZ42AD/dHNcOXAytGFBXBceDY7Pe8VKFtUTjjTMAby0TAJgNNiyntkK8S7LhR0i1hNK5PJHV8isr/EohR6DeZE9SQrGk37uvLXmrp9AVNEllG7mTZH+Z6xOA5s9GK40yeKONSzlfkCgYEA4dgKBC3uNtlKeXKCHezE4T6ZSUdKHD1tf9AvvG8pgKNnqU3agoYXWTL2qKJTXNyXenYiSPZbvCQKa7Zidth4juwx6lzkIAhvMpi/xtUOh5avCoiKuB2ii66+JU3/eBwbKgWG58jFX7n1A8hL/jSDDe5PAW2uMseagMpp2RbcVFg=",

		//编码格式
		'charset' => "UTF-8",

		//支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

		//应用ID
		'app_id' => "2018052360189175",

		//异步通知地址,只有扫码支付预下单可用
		'notify_url' => "https://coupons.keyouxinxi.com/template/alipayNotify.php",

		//最大查询重试次数
		'MaxQueryRetry' => "10",

		//查询间隔
		'QueryDuration' => "3"
);
