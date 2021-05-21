try:
	from SimpleSession.versions.v37 import beginRestore,\
	    registerAfterModelsCB, reportRestoreError
except ImportError:
	from chimera import UserError
	raise UserError('Cannot open session that was saved in a'
	    ' newer version of Chimera; update your version')
import chimera
from chimera import replyobj
replyobj.status('Beginning session restore...', \
    blankAfter=0)
beginRestore()

def restoreCoreModels():
	from SimpleSession.versions.v37 import init, restoreCamera, \
	     restoreMolecules, restoreColors, restoreSurfaces, \
	     restoreVRML, restorePseudoBondGroups
	molInfo = {'ballScale': (0, None, {}), 'ribbonHidesMainchain': (0, None, {}), 'pointSize': (0, None, {}), 'name': (0, None, {}), 'color': (0, None, {}), 'optional': {}, 'pdbHeaders': [], 'ids': (0, None, {}), 'surfaceOpacity': (0, None, {}), 'autochain': (0, None, {}), 'vdwDensity': (0, None, {}), 'hidden': (0, None, {}), 'lineWidth': (0, None, {}), 'stickScale': (0, None, {}), 'display': (0, None, {})}
	resInfo = {'insert': (0, None, {}), 'name': (0, None, {}), 'chain': (0, None, {}), 'ss': (0, None, {}), 'molecule': (0, None, {}), 'ribbonColor': (0, None, {}), 'label': (0, None, {}), 'labelColor': (0, None, {}), 'isHet': (0, None, {}), 'position': [], 'ribbonDisplay': (0, None, {}), 'optional': {}, 'ribbonDrawMode': (0, None, {})}
	atomInfo = {'residue': (0, None, {}), 'vdwColor': (0, None, {}), 'name': (0, None, {}), 'vdw': (0, None, {}), 'surfaceDisplay': (0, None, {}), 'color': (0, None, {}), 'idatmType': (0, None, {}), 'altLoc': (0, None, {}), 'label': (0, None, {}), 'surfaceOpacity': (0, None, {}), 'element': (0, None, {}), 'labelColor': (0, None, {}), 'surfaceColor': (0, None, {}), 'radius': (0, None, {}), 'surfaceCategory': (0, None, {}), 'drawMode': (0, None, {}), 'optional': {}, 'display': (0, None, {})}
	bondInfo = {'optional': {}, 'drawMode': (0, None, {}), 'radius': (0, None, {}), 'display': (0, None, {}), 'atoms': []}
	crdInfo = {}
	surfInfo = {'category': (0, None, {}), 'probeRadius': (0, None, {}), 'name': [], 'density': (0, None, {}), 'colorMode': (0, None, {}), 'molecule': [], 'allComponents': (0, None, {}), 'drawMode': (0, None, {}), 'display': (0, None, {}), 'customColors': []}
	vrmlInfo = {'subid': (0, None, {}), 'display': (0, None, {}), 'id': (0, None, {}), 'vrmlString': [], 'name': (0, None, {})}
	colors = {'Ru': ((0.141176, 0.560784, 0.560784), 1, ''), 'Ni': ((0.313725, 0.815686, 0.313725), 1, ''), 'Re': ((0.14902, 0.490196, 0.670588), 1, ''), 'Rf': ((0.8, 0, 0.34902), 1, ''), 'Ra': ((0, 0.490196, 0), 1, ''), 'Rb': ((0.439216, 0.180392, 0.690196), 1, ''), 'Rn': ((0.258824, 0.509804, 0.588235), 1, ''), 'Rh': ((0.0392157, 0.490196, 0.54902), 1, ''), 'Be': ((0.760784, 1, 0), 1, ''), 'Ba': ((0, 0.788235, 0), 1, ''), 'Bh': ((0.878431, 0, 0.219608), 1, ''), 'Bi': ((0.619608, 0.309804, 0.709804), 1, ''), 'Bk': ((0.541176, 0.309804, 0.890196), 1, ''), 'Br': ((0.65098, 0.160784, 0.160784), 1, ''), '_openColor00': ((1, 1, 1), 1, 'default'), '_openColor01': ((1, 0, 1), 1, 'default'), '_openColor02': ((0, 1, 1), 1, 'default'), '_openColor03': ((1, 1, 0), 1, 'default'), '_openColor04': ((1, 0, 0), 1, 'default'), '_openColor05': ((0, 0, 1), 1, 'default'), '_openColor06': ((0.67, 1, 0), 1, 'default'), '_openColor07': ((0.67, 0, 1), 1, 'default'), '_openColor08': ((0.67, 1, 1), 1, 'default'), 'H': ((1, 1, 1), 1, ''), 'P': ((1, 0.501961, 0), 1, ''), 'Os': ((0.14902, 0.4, 0.588235), 1, ''),
'Ge': ((0.4, 0.560784, 0.560784), 1, ''), 'Gd': ((0.270588, 1, 0.780392), 1, ''), 'Ga': ((0.760784, 0.560784, 0.560784), 1, ''), 'Pr': ((0.85098, 1, 0.780392), 1, ''), '_openColor12': ((1, 1, 0.5), 1, 'default'), '_openColor11': ((1, 0.67, 1), 1, 'default'), '_openColor10': ((0, 0.67, 1), 1, 'default'), 'Pt': ((0.815686, 0.815686, 0.878431), 1, ''), 'Pu': ((0, 0.419608, 1), 1, ''), 'C': ((0.564706, 0.564706, 0.564706), 1, ''), 'Pb': ((0.341176, 0.34902, 0.380392), 1, ''), 'Pa': ((0, 0.631373, 1), 1, ''), 'Pd': ((0, 0.411765, 0.521569), 1, ''), 'Xe': ((0.258824, 0.619608, 0.690196), 1, ''), 'Po': ((0.670588, 0.360784, 0), 1, ''), 'Pm': ((0.639216, 1, 0.780392), 1, ''), 'Hs': ((0.901961, 0, 0.180392), 1, ''), 'Ho': ((0, 1, 0.611765), 1, ''), 'Hf': ((0.301961, 0.760784, 1), 1, ''), 'Hg': ((0.721569, 0.721569, 0.815686), 1, ''), 'He': ((0.85098, 1, 1), 1, ''), 'Md': ((0.701961, 0.0509804, 0.65098), 1, ''), 'Mg': ((0.541176, 1, 0), 1, ''), 'K': ((0.560784, 0.25098, 0.831373), 1, ''), 'Mn': ((0.611765, 0.478431, 0.780392), 1, ''), 'O': ((1, 0.0509804, 0.0509804), 1, ''),
'Mt': ((0.921569, 0, 0.14902), 1, ''), 'S': ((1, 1, 0.188235), 1, ''), 'W': ((0.129412, 0.580392, 0.839216), 1, ''), 'Zn': ((0.490196, 0.501961, 0.690196), 1, ''), 'Eu': ((0.380392, 1, 0.780392), 1, ''), 'Zr': ((0.580392, 0.878431, 0.878431), 1, ''), 'Er': ((0, 0.901961, 0.458824), 1, ''), '_openColor13': ((1, 0, 0.5), 1, 'default'), 'No': ((0.741176, 0.0509804, 0.529412), 1, ''), 'Na': ((0.670588, 0.360784, 0.94902), 1, ''), 'Nb': ((0.45098, 0.760784, 0.788235), 1, ''), 'Nd': ((0.780392, 1, 0.780392), 1, ''), 'Ne': ((0.701961, 0.890196, 0.960784), 1, ''), 'Np': ((0, 0.501961, 1), 1, ''), 'Fr': ((0.258824, 0, 0.4), 1, ''), '_openColor15': ((0.67, 0.67, 1), 1, 'default'), '_openColor14': ((0, 1, 0.5), 1, 'default'), 'Fe': ((0.878431, 0.4, 0.2), 1, ''), 'Fm': ((0.701961, 0.121569, 0.729412), 1, ''), 'B': ((1, 0.709804, 0.709804), 1, ''), 'F': ((0.564706, 0.878431, 0.313725), 1, ''), 'Sr': ((0, 1, 0), 1, ''), 'N': ((0.188235, 0.313725, 0.972549), 1, ''), 'Kr': ((0.360784, 0.721569, 0.819608), 1, ''), 'Si': ((0.941176, 0.784314, 0.627451), 1, ''), 'Sn': ((0.4, 0.501961, 0.501961), 1, ''),
'Sm': ((0.560784, 1, 0.780392), 1, ''), 'V': ((0.65098, 0.65098, 0.670588), 1, ''), 'Sc': ((0.901961, 0.901961, 0.901961), 1, ''), 'Sb': ((0.619608, 0.388235, 0.709804), 1, ''), 'Sg': ((0.85098, 0, 0.270588), 1, ''), 'Se': ((1, 0.631373, 0), 1, ''), 'Co': ((0.941176, 0.564706, 0.627451), 1, ''), 'Cm': ((0.470588, 0.360784, 0.890196), 1, ''), 'Cl': ((0.121569, 0.941176, 0.121569), 1, ''), 'Ca': ((0.239216, 1, 0), 1, ''), 'Cf': ((0.631373, 0.211765, 0.831373), 1, ''), 'Ce': ((1, 1, 0.780392), 1, ''), 'Cd': ((1, 0.85098, 0.560784), 1, ''), 'Tm': ((0, 0.831373, 0.321569), 1, ''), 'Cs': ((0.341176, 0.0901961, 0.560784), 1, ''), 'Cr': ((0.541176, 0.6, 0.780392), 1, ''), 'Cu': ((0.784314, 0.501961, 0.2), 1, ''), 'La': ((0.439216, 0.831373, 1), 1, ''), 'Li': ((0.8, 0.501961, 1), 1, ''), 'Tl': ((0.65098, 0.329412, 0.301961), 1, ''), 'Lu': ((0, 0.670588, 0.141176), 1, ''), 'Lr': ((0.780392, 0, 0.4), 1, ''), 'Th': ((0, 0.729412, 1), 1, ''), 'Ti': ((0.74902, 0.760784, 0.780392), 1, ''), 'Te': ((0.831373, 0.478431, 0), 1, ''), 'Tb': ((0.188235, 1, 0.780392), 1, ''), 'Tc': ((0.231373, 0.619608, 0.619608), 1, ''),
'Ta': ((0.301961, 0.65098, 1), 1, ''), 'Yb': ((0, 0.74902, 0.219608), 1, ''), 'Db': ((0.819608, 0, 0.309804), 1, ''), 'Dy': ((0.121569, 1, 0.780392), 1, ''), '_openColor09': ((1, 0.67, 0), 1, 'default'), 'At': ((0.458824, 0.309804, 0.270588), 1, ''), 'I': ((0.580392, 0, 0.580392), 1, ''), 'medium purple': ((0.576471, 0.439216, 0.858824), 1, 'default'), 'U': ((0, 0.560784, 1), 1, ''), 'Y': ((0.580392, 1, 1), 1, ''), 'Ac': ((0.439216, 0.670588, 0.980392), 1, ''), 'Ag': ((0.752941, 0.752941, 0.752941), 1, ''), 'Ir': ((0.0901961, 0.329412, 0.529412), 1, ''), 'Am': ((0.329412, 0.360784, 0.94902), 1, ''), 'Al': ((0.74902, 0.65098, 0.65098), 1, ''), 'As': ((0.741176, 0.501961, 0.890196), 1, ''), 'Ar': ((0.501961, 0.819608, 0.890196), 1, ''), 'Au': ((1, 0.819608, 0.137255), 1, ''), 'Es': ((0.701961, 0.121569, 0.831373), 1, ''), 'In': ((0.65098, 0.458824, 0.45098), 1, ''), 'Mo': ((0.329412, 0.709804, 0.709804), 1, '')}
	materials = {'': ((0.85, 0.85, 0.85), 30), 'default': ((0.85, 0.85, 0.85), 30)}
	pbInfo = {'category': ['distance monitor'], 'bondInfo': [{'color': (0, None, {}), 'atoms': [], 'label': (0, None, {}), 'halfbond': (0, None, {}), 'labelColor': (0, None, {}), 'drawMode': (0, None, {}), 'display': (0, None, {})}], 'lineType': (1, 2, {}), 'color': (1, 0, {}), 'showStubBonds': (1, False, {}), 'lineWidth': (1, 1, {}), 'stickScale': (1, 1, {}), 'id': [-2]}
	colorInfo = {0: ('yellow', (1, 1, 0, 1)), 1: ('', (1, 1, 1, 1)), 2: ('green', (0, 1, 0, 1))}
	detail = 1
	viewerFog = None
	viewerBG = 1
	viewerHL = 2
	viewerLB = 2
	viewerAttrs = {'silhouetteColor': None, 'yonIntensity': 0, 'showSilhouette': False, 'startRatio': 0.4, 'viewSize': 92.1251, 'scaleFactor': 1.39097, 'silhouetteWidth': 1, 'depthCue': True, 'highlight': 0, 'lensBorder': True}
	cameraAttrs = {'center': (-7.54457, 51.0755, -67.6229), 'fieldOfView': 25, 'nearFar': (-6.1712, -129.075), 'ortho': True, 'eyeSeparation': 50.8, 'focal': -67.6229}
	cameraMode = 'mono'

	replyobj.status("Initializing session restore...", blankAfter=0)
	init(colorInfo)
	replyobj.status("Restoring colors...", blankAfter=0)
	restoreColors(colors, materials)
	replyobj.status("Restoring molecules...", blankAfter=0)
	restoreMolecules(molInfo, resInfo, atomInfo, bondInfo, crdInfo)
	replyobj.status("Restoring surfaces...", blankAfter=0)
	restoreSurfaces(surfInfo)
	replyobj.status("Restoring VRML models...", blankAfter=0)
	restoreVRML(vrmlInfo)
	replyobj.status("Restoring pseudobond groups...", blankAfter=0)
	restorePseudoBondGroups(pbInfo)
	replyobj.status("Restoring camera...", blankAfter=0)
	restoreCamera(detail, viewerFog, viewerBG, viewerHL, viewerLB, viewerAttrs, cameraAttrs, cameraMode)
	replyobj.status("Restoring other models...", blankAfter=0)

try:
	restoreCoreModels()
except:
	reportRestoreError("Error restoring core models")

	replyobj.status("Restoring extension info...", blankAfter=0)


def restore_surface_zones():
 surface_zone_state = \
   {
    'class': 'Surface_Zone_State',
    'version': 1,
    'zone_table': {},
   }
 try:
  import SurfaceZone.session
  SurfaceZone.session.restore_surface_zone_state(surface_zone_state)
 except:
  reportRestoreError('Error restoring surface zones')

registerAfterModelsCB(restore_surface_zones)


def restore_surface_color_mapping():
 try:
  surface_color_state = \
   {
    'class': 'Surface_Colorings_State',
    'coloring_table': {
      ( 0, 0, ): (
        {
         'class': 'Volume_Color_State',
         'colormap': {
           'class': 'Color_Map_State',
           'color_above_value_range': (
             0.0,
             0.0,
             1.0,
             1.0,
            ),
           'color_below_value_range': (
             1.0,
             0.0,
             0.0,
             1.0,
            ),
           'color_no_value': ( 0.5, 0.5, 0.5, 1, ),
           'colors': [
             (
              1.0,
              0.0,
              0.0,
              1.0,
             ),
             (
              1.0,
              1.0,
              0.0,
              1.0,
             ),
             (
              0.0,
              1.0,
              0.0,
              1.0,
             ),
             (
              0.0,
              1.0,
              1.0,
              1.0,
             ),
             (
              0.0,
              0.0,
              1.0,
              1.0,
             ),
            ],
           'data_values': [
             192.40000000000001,
             473.39999999999998,
             754.39999999999998,
             1035.0,
             1316.0,
            ],
           'version': 1,
          },
         'session_volume_id': 41047680,
         'version': 2,
        },
        True,
       ),
     },
    'geometry': '381x362+1600+708',
    'is_visible': False,
    'version': 2,
   }
  import SurfaceColor.session
  SurfaceColor.session.restore_surface_color_state(surface_color_state)
 except:
  reportRestoreError('Error restoring surface color mapping')

registerAfterModelsCB(restore_surface_color_mapping)


def restore_surface_capping():
 capper_state = \
  {
   'cap_offset': '0.001',
   'cap_rgba': ( 1, 1, 1, 1.0, ),
   'cap_style': 'solid',
   'class': 'Capper_Dialog_State',
   'color_caps': 0,
   'geometry': '227x164+2108+637',
   'is_visible': False,
   'show_caps': True,
   'subdivision_factor': '1.0',
   'version': 1,
  }
 import SurfaceCap.session
 SurfaceCap.session.restore_capper_state(capper_state)

try:
  restore_surface_capping()
except:
  reportRestoreError('Error restoring surface capping')


def restore_scale_bar():
 scale_bar_state = \
  {
   'bar_length': '25',
   'bar_rgba': ( 0, 0, 0, 1, ),
   'bar_thickness': '2.5',
   'class': 'Scale_Bar_Dialog_State',
   'frozen_models': [ ],
   'geometry': '293x198+2144+578',
   'is_visible': False,
   'label_rgba': ( 0, 0, 0, 1, ),
   'label_text': u'# \u212b',
   'label_x_offset': '-8',
   'label_y_offset': '1',
   'model': {
     'active': False,
     'class': 'Model_State',
     'clip_plane_normal': ( 0.0, 0.0, 0.0, ),
     'clip_plane_origin': ( 0.0, 0.0, 0.0, ),
     'clip_thickness': 5.0,
     'display': True,
     'id': 9,
     'name': 'scale bar',
     'osl_identifier': '#9',
     'subid': 0,
     'use_clip_plane': False,
     'use_clip_thickness': False,
     'version': 4,
     'xform': {
       'class': 'Xform_State',
       'rotation_angle': 0.0,
       'rotation_axis': ( 0.0, 0.0, 1.0, ),
       'translation': ( 42.526022214412578, -12.50617205907912, -11.194106704635701, ),
       'version': 1,
      },
    },
   'move_scalebar': 0,
   'orientation': 'horizontal',
   'preserve_position': 1,
   'screen_x_position': '0.76',
   'screen_y_position': '-0.96',
   'show_scalebar': True,
   'version': 1,
  }
 import ScaleBar.session
 ScaleBar.session.restore_scale_bar_state(scale_bar_state)

try:
  restore_scale_bar()
except:
  reportRestoreError('Error restoring scale bar')

def restore_volume_viewer():
 volume_viewer_state = \
  {
   'adjust_camera': False,
   'auto_show_subregion': False,
   'box_padding': '0',
   'class': 'Volume_Dialog_State',
   'data_and_regions_state': [
     (
      {
       'available_subsamplings': {},
       'class': 'Data_State',
       'file_type': 'mrc',
       'path': 'emd_%id%.map',
       'version': 1,
       'xyz_origin': None,
       'xyz_step': None,
      },
      [
       {
        'class': 'Data_Region_State',
        'component_display_parameters': [
          {
           'class': 'Component_Display_Parameters_State',
           'default_rgba': ( 0.69999999999999996, 0.69999999999999996, 0.69999999999999996, 1, ),
           'hidden': 0,
           'solid_brightness_factor': 1.0,
           'solid_colors': [
             ( 1.0, 1.0, 1.0, 1, ),
             ( 1.0, 1.0, 1.0, 1, ),
             ( 1.0, 1.0, 1.0, 1, ),
            ],
           'solid_levels': [
             ( 0.073516859084367753, 0, ),
             ( 0.15603967680037023, 0.5, ),
             ( 0.23856249451637268, 1, ),
            ],
           'surface_brightness_factor': 1.0,
           'surface_colors': [
             ( %surf_color% ),
            ],
           'surface_levels': [ %thr%, ],
           'transparency_depth': 1.6000000238400001,
           'transparency_factor': 0.0,
           'version': 1,
          },
         ],
        'name': '',
        'region': (
          [ 0, 0, 0, ],
          [ 500, 500, 500, ],
          [ 1, 1, 1, ],
         ),
        'region_list': {
          'class': 'Region_List_State',
          'current_index': 0,
          'named_regions': [ ],
          'region_list': [
            (
             ( 0, 0, 0, ),
             ( 127, 127, 127, ),
            ),
           ],
          'version': 1,
         },
        'rendering_options': {
          'bt_correction': False,
          'class': 'Rendering_Options_State',
          'colormap_size': 256,
          'dim_transparency': True,
          'flip_normals': True,
          'line_thickness': 1.0,
          'linear_interpolation': True,
          'maximum_intensity_projection': False,
          'mesh_lighting': True,
          'minimal_texture_memory': False,
          'outline_box_rgb': ( 1, 1, 1, ),
          'show_outline_box': False,
          'smooth_lines': True,
          'smoothing_factor': 0.29999999999999999,
          'smoothing_iterations': 2,
          'square_mesh': False,
          'subdivide_surface': False,
          'subdivision_levels': 1,
          'surface_smoothing': False,
          'two_sided_lighting': True,
          'use_2d_textures': True,
          'use_colormap': True,
          'version': 1,
         },
        'representation': 'surface',
        'solid_model': None,
        'surface_model': {
          'active': True,
          'class': 'Model_State',
          'clip_plane_normal': ( 0.0, 0.0, -1.0, ),
          'clip_plane_origin': ( 0.0, 0.0, 0.0, ),
          'clip_thickness': 1.0,
          'display': True,
          'id': 0,
          'name': 'emd_%id%.map',
          'osl_identifier': '#0',
          'subid': 0,
          'use_clip_plane': False,
          'use_clip_thickness': False,
          'version': 4,
#          'xform': {
#            'class': 'Xform_State',
#            'rotation_angle': 105.98368679959076,
#            'rotation_axis': ( 0.17145391355721956, -0.96075407346704433, -0.21807146957452903, ),
#            'translation': ( 1244.8264328796683, 106.01476920207355, -296.45554904905907, ),
#            'version': 1,
#           },
          'xform': {
            'class': 'Xform_State',
            'rotation_angle': 0,
            'rotation_axis': ( 1, 0, 0, ),
            'translation': ( 0, 0, 0, ),
            'version': 1,
           },
         },
        'version': 2,
       },
      ],
     ),
    ],
   'data_cache_size': '32',
   'focus_region_name': '',
   'geometry': '390x765+2689+86',
   'immediate_update': True,
   'is_visible': False,
   'limit_voxel_count': 0,
   'no_colormap_multi_component_data': True,
   'representation': 'surface',
   'selectable_subregions': False,
   'show_on_open': True,
   'shown_panels': [
     'Feature buttons',
     'Data and Step menus',
     'Data set list',
     'Precomputed subsamples',
     'Origin and Scale',
     'Display style',
     'Threshold and Color',
     'Brightness and Transparency',
     'Region bounds',
     'Zone',
     'Atom box',
     'Named regions',
     'Surface and Mesh options',
    ],
   'subregion_button': 'button 2',
   'version': 7,
   'voxel_limit': '1',
   'voxel_limit_for_open': '256',
   'zone_radius': 2.0,
  }
 import VolumeViewer.session
 VolumeViewer.session.restore_volume_state(volume_viewer_state)

try:
  restore_volume_viewer()
except:
  reportRestoreError('Error restoring volume viewer')


def restore_volume_dialog():
 volume_dialog_state = \
  {
   'adjust_camera': 0,
   'auto_show_subregion': 0,
   'box_padding': '0',
   'class': 'Volume_Dialog_State',
   'data_cache_size': '32',
   'focus_volume': 41047680,
   'geometry': '404x900+2689+86',
   'histogram_active_order': [ 0, ],
   'histogram_volumes': [ 41047680, ],
   'immediate_update': 1,
   'initial_colors': (
     ( 0.69999999999999996, 0.69999999999999996, 0.69999999999999996, 1, ),
     ( 1, 1, 0.69999999999999996, 1, ),
     ( 0.69999999999999996, 1, 1, 1, ),
     ( 0.69999999999999996, 0.69999999999999996, 1, 1, ),
     ( 1, 0.69999999999999996, 1, 1, ),
     ( 1, 0.69999999999999996, 0.69999999999999996, 1, ),
     ( 0.69999999999999996, 1, 0.69999999999999996, 1, ),
     ( 0.90000000000000002, 0.75, 0.59999999999999998, 1, ),
     ( 0.59999999999999998, 0.75, 0.90000000000000002, 1, ),
     ( 0.80000000000000004, 0.80000000000000004, 0.59999999999999998, 1, ),
    ),
   'is_visible': False,
   'max_histograms': '3',
   'representation': 'surface',
   'selectable_subregions': 0,
   'show_on_open': 1,
   'show_plane': 1,
   'shown_panels': [
     'Feature buttons',
     'Data set list',
     'Precomputed subsamples',
     'Coordinates',
     'Threshold and Color',
     'Brightness and Transparency',
     'Display style',
     'Region bounds',
     'Zone',
     'Atom box',
     'Named regions',
     'Surface and Mesh options',
    ],
   'subregion_button': 'button 2',
   'use_initial_colors': 1,
   'version': 12,
   'voxel_limit_for_open': '256',
   'voxel_limit_for_plane': '256',
   'zone_radius': 2.0,
  }
 from VolumeViewer import session
 session.restore_volume_dialog_state(volume_dialog_state)

try:
  restore_volume_dialog()
except:
  reportRestoreError('Error restoring volume dialog')


def restoreLightController():
	import Lighting
	c = Lighting.get().setFromParams({'shininess': (30.0, (0.84999999999999998, 0.84999999999999998, 0.84999999999999998), 1.0), 'key': (True, (0.47826086956521729, 0.99999999999999989, 1.0), 0.60000002384185791, (1.0, 1.0, 1.0), 0.80000001192092896, (-0.068838099320371271, 0.66571268564074937, 0.74302620159651145)), 'fill': (True, (1.0, 0.0, 0.0), 0.34999999403953552, (1.0, 0.0, 0.0), 0.34999999403953552, (0.58728985988801152, -0.38683124544073505, 0.71095162143671176))})
try:
	restoreLightController()
except:
	reportRestoreError("Error restoring lighting parameters")


def restoreSession_RibbonStyleEditor():
	import SimpleSession
	import RibbonStyleEditor
	userScalings = []
	userXSections = []
	userResidueClasses = []
	residueData = []
	SimpleSession.registerAfterModelsCB(RibbonStyleEditor.restoreState,
				(userScalings, userXSections,
				userResidueClasses, residueData))
try:
	restoreSession_RibbonStyleEditor()
except:
	reportRestoreError("Error restoring RibbonStyleEditor state")

def restoreMidasText():
	from Midas import midas_text
	midas_text.aliases = {}
	midas_text.userSurfCategories = {}


def restoreRemainder():
	from SimpleSession.versions.v37 import restoreWindowSize, \
	     restoreOpenStates, restoreSelections, restoreFontInfo, \
	     restoreOpenModelsAttrs, restoreModelClip

	curSelIds =  []
	savedSels = []
	openModelsAttrs = { 'cofrMethod': 3 }
	windowSize = (500, 500)
	xformMap = {}
	fontInfo = {'face': ('Sans Serif', 'Normal', 30)}
	clipPlaneInfo = {}

	replyobj.status("Restoring window...", blankAfter=0)
	restoreWindowSize(windowSize)
	replyobj.status("Restoring open states...", blankAfter=0)
	restoreOpenStates(xformMap)
	replyobj.status("Restoring font info...", blankAfter=0)
	restoreFontInfo(fontInfo)
	replyobj.status("Restoring selections...", blankAfter=0)
	restoreSelections(curSelIds, savedSels)
	replyobj.status("Restoring openModel attributes...", blankAfter=0)
	restoreOpenModelsAttrs(openModelsAttrs)
	replyobj.status("Restoring model clipping...", blankAfter=0)
	restoreModelClip(clipPlaneInfo)

	replyobj.status("Restoring remaining extension info...", blankAfter=0)
try:
	restoreRemainder()
except:
	reportRestoreError("Error restoring post-model state")
from SimpleSession.versions.v37 import makeAfterModelsCBs
makeAfterModelsCBs()

from SimpleSession.versions.v37 import endRestore
replyobj.status('Finishing restore...', blankAfter=0)
endRestore()
replyobj.status('Restore finished.')

