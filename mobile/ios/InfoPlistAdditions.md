# Info.plist — Required Usage Description Keys

Add these keys to `ios/VillaCRMMobile/Info.plist` before submitting to the App Store.
Apple will reject the app without them.

```xml
<!-- Microphone — required for VoIP calls -->
<key>NSMicrophoneUsageDescription</key>
<string>VillaCRM uses your microphone to make and receive business calls with your leads.</string>

<!-- Camera — optional, for document scanning -->
<key>NSCameraUsageDescription</key>
<string>VillaCRM can use your camera to scan documents or upload property photos.</string>

<!-- Contacts — optional, to show CRM leads in the native contacts app -->
<key>NSContactsUsageDescription</key>
<string>VillaCRM can sync your business leads to your contacts app for easy dialling.</string>

<!-- Face ID / Touch ID — biometric app lock -->
<key>NSFaceIDUsageDescription</key>
<string>VillaCRM uses Face ID to quickly unlock the app without your password.</string>

<!-- Location — for viewing check-in (optional) -->
<key>NSLocationWhenInUseUsageDescription</key>
<string>VillaCRM records your location when you check in at a property viewing.</string>

<!-- Background modes — required for VoIP -->
<!-- In Xcode: Signing & Capabilities → Background Modes → Voice over IP, Remote notifications -->

<!-- CallKit entitlement — required for incoming call UI -->
<!-- In Xcode: Signing & Capabilities → Add Capability → CallKit -->

<!-- PushKit entitlement — required for VoIP push (incoming calls wake device) -->
<!-- In Xcode: Signing & Capabilities → Add Capability → Push Notifications -->
<!-- Then add NSVoIPCallNotificationKey in Info.plist -->
```

## App Store Review Notes
Include this in the "Notes for App Store Reviewer" field when submitting:
> This app requires an active VillaCRM agency account to log in. Test credentials:
> Email: reviewer@villacrm-demo.com  Password: ReviewPass123!
> The app records calls only when the agent initiates or receives a call through the app.
> A consent announcement ("This call may be recorded…") plays automatically at the start of every call.
