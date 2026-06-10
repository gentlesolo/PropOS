package com.facebook.react;

import android.app.Application;
import android.content.Context;
import android.content.res.Resources;

import com.facebook.react.ReactPackage;
import com.facebook.react.shell.MainPackageConfig;
import com.facebook.react.shell.MainReactPackage;
import java.util.Arrays;
import java.util.ArrayList;

@SuppressWarnings("deprecation")
public class PackageList {
  private Application application;
  private ReactNativeHost reactNativeHost;
  private MainPackageConfig mConfig;

  public PackageList(ReactNativeHost reactNativeHost) {
    this(reactNativeHost, null);
  }

  public PackageList(Application application) {
    this(application, null);
  }

  public PackageList(ReactNativeHost reactNativeHost, MainPackageConfig config) {
    this.reactNativeHost = reactNativeHost;
    mConfig = config;
  }

  public PackageList(Application application, MainPackageConfig config) {
    this.reactNativeHost = null;
    this.application = application;
    mConfig = config;
  }

  private ReactNativeHost getReactNativeHost() {
    return this.reactNativeHost;
  }

  private Resources getResources() {
    return this.getApplication().getResources();
  }

  private Application getApplication() {
    if (this.reactNativeHost == null) return this.application;
    return this.reactNativeHost.getApplication();
  }

  private Context getApplicationContext() {
    return this.getApplication().getApplicationContext();
  }

  public ArrayList<ReactPackage> getPackages() {
    return new ArrayList<>(Arrays.<ReactPackage>asList(
      new MainReactPackage(mConfig),
      // @notifee/react-native
      new io.invertase.notifee.NotifeePackage(),
      // @react-native-async-storage/async-storage
      new org.asyncstorage.AsyncStoragePackage(),
      // @react-native-firebase/app
      new io.invertase.firebase.app.ReactNativeFirebaseAppPackage(),
      // @react-native-firebase/messaging
      new io.invertase.firebase.messaging.ReactNativeFirebaseMessagingPackage(),
      // @sentry/react-native
      new io.sentry.react.RNSentryPackage(),
      // @twilio/voice-react-native-sdk
      new com.twiliovoicereactnative.TwilioVoiceReactNativePackage(),
      // react-native-audio-recorder-player
      new com.margelo.nitro.audiorecorderplayer.AudioRecorderPlayerPackage(),
      // react-native-biometrics
      new com.rnbiometrics.ReactNativeBiometricsPackage(),
      // react-native-callkeep
      new io.wazo.callkeep.RNCallKeepPackage(),
      // react-native-keychain
      new com.oblador.keychain.KeychainPackage(),
      // react-native-localize
      new com.zoontek.rnlocalize.RNLocalizePackage(),
      // react-native-mmkv
      new com.margelo.nitro.mmkv.NitroMmkvPackage(),
      // react-native-nitro-modules
      new com.margelo.nitro.NitroModulesPackage(),
      // react-native-safe-area-context
      new com.th3rdwave.safeareacontext.SafeAreaContextPackage(),
      // react-native-screens
      new com.swmansion.rnscreens.RNScreensPackage(),
      // react-native-vector-icons
      new com.oblador.vectoricons.VectorIconsPackage()
    ));
  }
}